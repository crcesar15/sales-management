<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductOption extends Model
{
    /** @use HasFactory<ProductOptionFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
    ];

    /** @return BelongsTo<Product, $this>*/
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return HasMany<ProductOptionValue, $this>*/
    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class);
    }
}
