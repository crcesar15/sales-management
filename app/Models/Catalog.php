<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CatalogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Catalog extends Model
{
    /** @use HasFactory<CatalogFactory> */
    use HasFactory;

    protected $table = 'catalog';

    protected $fillable = [
        'vendor_id',
        'product_variant_id',
        'unit_id',
        'price',
        'payment_terms',
        'details',
        'status',
        'minimum_order_quantity',
        'lead_time_days',
    ];

    /** @return BelongsTo<Vendor, $this> */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /** @return BelongsTo<ProductVariantUnit, $this> */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductVariantUnit::class, 'unit_id');
    }
}
