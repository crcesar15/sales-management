<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CatalogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Catalog extends Model
{
    /** @use HasFactory<CatalogFactory> */
    use HasFactory;

    protected $table = 'catalog';

    protected $fillable = [
        'vendor_id',
        'product_variant_id',
        'price',
        'payment_terms',
        'details',
    ];
}
