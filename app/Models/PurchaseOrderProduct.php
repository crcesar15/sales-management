<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DiscountType;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

final class PurchaseOrderProduct extends Model
{
    use LogsActivity;

    protected $table = 'purchase_order_product';

    protected $fillable = [
        'purchase_order_id',
        'product_variant_id',
        'catalog_id',
        'unit_id',
        'quantity',
        'price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'pricing_reference',
        'total',
    ];

    protected $appends = ['received_quantity', 'remaining_quantity', 'gross_total'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName('purchase_order_product')->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<PurchaseOrder, $this> */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /** @return BelongsTo<Catalog, $this> */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class);
    }

    /** @return BelongsTo<ProductVariantUnit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductVariantUnit::class);
    }

    /** @return HasMany<ReceptionOrderProduct, $this> */
    public function receptionOrderItems(): HasMany
    {
        return $this->hasMany(ReceptionOrderProduct::class, 'purchase_order_item_id');
    }

    /**
     * Total quantity received from completed receptions for this line item.
     * Computed by summing quantities from non-cancelled reception orders.
     */
    public function getReceivedQuantityAttribute(): string
    {
        $sum = $this->receptionOrderItems
            ->filter(fn (ReceptionOrderProduct $item) => $item->receptionOrder?->status === 'completed')
            ->sum('quantity');

        return number_format((float) $sum, 4, '.', '');
    }

    /**
     * Remaining quantity yet to be received for this line item.
     */
    public function getRemainingQuantityAttribute(): string
    {
        $received = (float) $this->received_quantity;

        return number_format((float) $this->quantity - $received, 4, '.', '');
    }

    /** @return Attribute<float, never> */
    protected function grossTotal(): Attribute
    {
        return Attribute::make(get: fn (): float => BigDecimal::of((string) $this->price)->multipliedBy((string) $this->quantity)->toScale(2, RoundingMode::HalfUp)->toFloat());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pricing_reference' => 'boolean',
            'quantity' => 'decimal:4',
            'price' => 'decimal:2',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}
