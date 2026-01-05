<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_option_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ProductOption $productOption
 *
 * @method static \Database\Factories\ProductOptionValueFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue whereProductOptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductOptionValue whereValue($value)
 *
 * @mixin \Eloquent
 */
final class ProductOptionValue extends Model
{
    /** @use HasFactory<\Database\Factories\ProductOptionValueFactory> */
    use HasFactory;

    protected $fillable = [
        'product_option_id',
        'value',
    ];

    /** @return BelongsTo<ProductOption, $this> */
    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }
}
