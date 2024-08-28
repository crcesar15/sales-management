<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'brand_id',
        'measure_unit_id',
        'name',
        'description',
        'options',
        'status',
    ];

    protected $appends = [
        'media',
    ];

    public function getMediaAttribute()
    {
        // Get images from variants
        $variants = $this->variants->load('media');

        if ($variants->count() === 1) {
            return $variants->first()->media;
        } else {
            $media = collect();
            foreach ($variants as $variant) {
                $media = $media->merge($variant->media);
            }

            return $media;
        }
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function measureUnit()
    {
        return $this->belongsTo(MeasureUnit::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
