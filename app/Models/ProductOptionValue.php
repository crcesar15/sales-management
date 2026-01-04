<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductOptionValue extends Model
{
    /** @use HasFactory<\Database\Factories\ProductOptionValueFactory> */
    use HasFactory;

    protected $fillable = [
        'product_option_id',
        'value',
    ];

    /** @return BelongsTo<ProductOption, $this> */
    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class);
    }
}
