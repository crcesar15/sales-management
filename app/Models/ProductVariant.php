<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'identifier',
        'name',
        'description',
        'price',
        'stock',
        'status',
    ];

    // protected $appends = [
    //     'media',
    // ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getMediaAttribute($value)
    {
        // Get images from variants
        $media = json_decode($value);

        $formattedMedia = [];

        if (count($media) > 0) {
            // Get images from product
            $product = $this->product->load('media');

            foreach ($media as $item) {
                $formattedMedia[] = $product->media->where('id', $item->id)->first();
            }
        }

        return $formattedMedia;
    }
}
