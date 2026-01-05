<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $product_variant_id
 * @property int $product_option_value_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantOptionValue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantOptionValue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantOptionValue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantOptionValue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantOptionValue whereProductOptionValueId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariantOptionValue whereProductVariantId($value)
 *
 * @mixin \Eloquent
 */
final class ProductVariantOptionValue extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_variant_id',
        'product_option_value_id',
    ];
}
