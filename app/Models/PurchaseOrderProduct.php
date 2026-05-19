<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseOrderProduct extends Model
{
    protected $table = 'purchase_order_product';

    protected $fillable = [
        'purchase_order_id',
        'product_variant_id',
        'catalog_id',
        'unit_id',
        'quantity',
        'price',
        'total',
    ];

    protected $appends = ['received_quantity', 'remaining_quantity'];

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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'price' => 'decimal:2',
            'total' => 'decimal:2',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}
