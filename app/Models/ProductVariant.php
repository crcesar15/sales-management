<?php

declare(strict_types=1);

namespace App\Models;

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
        'stock',
        'status',
    ];

    protected $appends = [
        'name',
    ];

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsToMany<Vendor, $this, Pivot> */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'catalog', 'product_variant_id', 'vendor_id')
            ->withPivot('price', 'payment_terms', 'details', 'status');
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

    /** @return HasMany<Batch, $this> */
    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('product_variant')
            ->dontSubmitEmptyLogs();
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
