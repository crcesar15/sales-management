<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'identifier',
        'name',
        'description',
        'price',
        'stock',
        'brand',
        'measure_unit',
        'category',
        'status',
        'options',
        'correlation_hash',
    ];

    //cast price to float
    protected $casts = [
        'price' => 'float',
        'stock' => 'float',
    ];

    public function media()
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function measureUnit()
    {
        return $this->belongsTo(MeasureUnit::class);
    }
}
