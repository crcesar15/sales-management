<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Catalog|null $catalogEntry
 */
final class ReceptionOrderProduct extends Model
{
    protected $table = 'reception_order_product';

    protected $fillable = [
        'reception_order_id',
        'product_variant_id',
        'quantity',
        'price',
        'total',
        'expiry_date',
    ];

    /** @return BelongsTo<ReceptionOrder, $this> */
    public function receptionOrder(): BelongsTo
    {
        return $this->belongsTo(ReceptionOrder::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
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
            'expiry_date' => 'date',
            'created_at' => 'datetime:Y-m-d H:i',
            'updated_at' => 'datetime:Y-m-d H:i',
        ];
    }
}
