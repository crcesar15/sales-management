<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MarginType;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    use LogsActivity;

    protected $fillable = [
        'product_id',
        'identifier',
        'barcode',
        'price',
        'purchase_price',
        'replacement_cost',
        'pricing_catalog_id',
        'replacement_cost_event_id',
        'reference_outdated',
        'margin_type',
        'margin_value',
        'minimum_stock_level',
        'has_expiration',
        'stock',
        'status',
    ];

    protected $appends = [
        'name',
    ];

    /** @return BelongsTo<Catalog, $this> */
    public function pricingCatalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'pricing_catalog_id');
    }

    /** @return BelongsTo<ReplacementCostEvent, $this> */
    public function replacementCostEvent(): BelongsTo
    {
        return $this->belongsTo(ReplacementCostEvent::class, 'replacement_cost_event_id');
    }

    /** @return HasMany<ReplacementCostEvent, $this> */
    public function replacementCostEvents(): HasMany
    {
        return $this->hasMany(ReplacementCostEvent::class);
    }

    /** @return HasMany<ReplacementCostEvent, $this> */
    public function pendingCostReviews(): HasMany
    {
        return $this->replacementCostEvents()->whereNull('invalidated_at')->whereNull('reviewed_at')->whereNotNull('previous_cost');
    }

    /** @return HasMany<ReplacementCostEvent, $this> */
    public function recentCostEvents(): HasMany
    {
        return $this->replacementCostEvents()->latest('id')->limit(30);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsToMany<Vendor, $this, Pivots\CatalogPivot> */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'catalog', 'product_variant_id', 'vendor_id')
            ->using(Pivots\CatalogPivot::class)
            ->withPivot('price', 'payment_terms', 'details', 'status', 'unit_id', 'minimum_order_quantity', 'lead_time_days');
    }

    /** @return BelongsToMany<Media, $this, Pivot> */
    public function images(): BelongsToMany
    {
        return $this->belongsToMany(
            Media::class,
            'media_product_variant',
            'product_variant_id',
            'media_id'
        )->withPivot('position')->orderByPivot('position', 'asc');
    }

    /** @return BelongsToMany<ProductOptionValue, $this> */
    public function values(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_option_values',
            'product_variant_id',
            'product_option_value_id'
        );
    }

    /** @return HasMany<Catalog, $this> */
    public function catalogEntries(): HasMany
    {
        return $this->hasMany(Catalog::class, 'product_variant_id');
    }

    /** @return HasMany<Batch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    /** @return HasMany<VariantStoreLocation, $this> */
    public function storeLocations(): HasMany
    {
        return $this->hasMany(VariantStoreLocation::class);
    }

    /** @return HasMany<StockTransferItem, $this> */
    public function stockTransferItems(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    /** @return HasMany<PurchaseOrderProduct, $this> */
    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderProduct::class);
    }

    /** @return HasMany<ReceptionOrderProduct, $this> */
    public function receptionOrderItems(): HasMany
    {
        return $this->hasMany(ReceptionOrderProduct::class);
    }

    /** @return HasMany<ProductVariantUnit, $this> */
    public function units(): HasMany
    {
        return $this->hasMany(ProductVariantUnit::class, 'product_variant_id');
    }

    /** @return HasMany<ProductVariantUnit, $this> */
    public function saleUnits(): HasMany
    {
        return $this->hasMany(ProductVariantUnit::class, 'product_variant_id')
            ->where('type', 'sale');
    }

    /** @return HasMany<ProductVariantUnit, $this> */
    public function activeSaleUnits(): HasMany
    {
        return $this->hasMany(ProductVariantUnit::class, 'product_variant_id')
            ->where('type', 'sale')
            ->where('status', 'active')
            ->orderBy('sort_order');
    }

    /** @return HasMany<ProductVariantUnit, $this> */
    public function purchaseUnits(): HasMany
    {
        return $this->hasMany(ProductVariantUnit::class, 'product_variant_id')
            ->where('type', 'purchase');
    }

    /** @return HasMany<ProductVariantUnit, $this> */
    public function activePurchaseUnits(): HasMany
    {
        return $this->hasMany(ProductVariantUnit::class, 'product_variant_id')
            ->where('type', 'purchase')
            ->where('status', 'active')
            ->orderBy('sort_order');
    }

    public function recalculateStock(): self
    {
        $this->stock = max(0, (int) $this->batches()->active()->sum('remaining_quantity'));
        $this->save();

        return $this;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('product_variant')
            ->dontSubmitEmptyLogs();
    }

    protected function casts(): array
    {
        return [
            'has_expiration' => 'boolean', 'purchase_price' => 'decimal:2',
            'replacement_cost' => 'decimal:12', 'reference_outdated' => 'boolean',
            'margin_type' => MarginType::class, 'margin_value' => 'decimal:2',
        ];
    }

    /** @return Attribute<string, never> */
    protected function name(): Attribute
    {
        $values = $this->values;

        $formatted = [];

        foreach ($values as $value) {
            $formatted[] = $value->option?->name . ': ' . $value->value;
        }

        return Attribute::make(
            get: fn () => implode(' / ', $formatted)
        );
    }
}
