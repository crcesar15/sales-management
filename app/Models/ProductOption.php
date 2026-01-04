<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Product|null $product
 * @method static \Database\Factories\ProductOptionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption query()
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductOptionValue> $values
 * @property-read int|null $values_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOption whereUpdatedAt($value)
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

    /** @return HasMany<ProductOptionValue, $this>*/
    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class);
    }
}
