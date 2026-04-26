<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BatchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Batch extends Model
{
    /** @use HasFactory<BatchFactory> */
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'reception_order_id',
        'store_id',
        'expiry_date',
        'initial_quantity',
        'remaining_quantity',
        'missing_quantity',
        'sold_quantity',
        'transferred_quantity',
        'status',
    ];

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /** @return BelongsTo<ReceptionOrder, $this> */
    public function receptionOrder(): BelongsTo
    {
        return $this->belongsTo(ReceptionOrder::class);
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @param  Builder<self>  $query */
    public function scopeActiveOrQueued(Builder $query): void
    {
        $query->whereIn('status', ['active', 'queued']);
    }
}
