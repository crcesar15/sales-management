<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ReceptionOrderProduct extends Model
{
    protected $table = 'reception_order_product';

    protected $fillable = [
        'reception_order_id',
        'purchase_order_item_id',
        'product_variant_id',
        'quantity',
        'conversion_factor',
        'price',
        'total',
    ];

    /** @return BelongsTo<ReceptionOrder, $this> */
    public function receptionOrder(): BelongsTo
    {
        return $this->belongsTo(ReceptionOrder::class);
    }

    /** @return BelongsTo<PurchaseOrderProduct, $this> */
    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderProduct::class, 'purchase_order_item_id');
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /** @return HasMany<ReceptionOrderBatchAllocation, $this> */
    public function batchAllocations(): HasMany
    {
        return $this->hasMany(ReceptionOrderBatchAllocation::class);
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
