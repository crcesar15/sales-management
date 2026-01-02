<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Product|null $product
 *
 * @method static \Database\Factories\ProductOptionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption query()
 *
 * @mixin \Eloquent
 */
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
}
