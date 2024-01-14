<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'name',
        'description',
        'price',
        'stock',
        'brand',
    ];

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }
}
