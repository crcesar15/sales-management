<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    use HasFactory;

    protected $table = 'catalog';

    protected $fillable = [
        'supplier_id',
        'product_variant_id',
        'price',
        'payment_terms',
        'details',
    ];
}
