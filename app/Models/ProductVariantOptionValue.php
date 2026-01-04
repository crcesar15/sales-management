<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ProductVariantOptionValue extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_variant_id',
        'product_option_value_id',
    ];
}
