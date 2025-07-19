<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ProductVariant extends Model
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
        'media',
    ];

    // protected $appends = [
    //     'media',
    // ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'catalog', 'product_variant_id', 'vendor_id')
            ->withPivot('price', 'payment_terms', 'details', 'status');
    }

    /**
     * @return list
     */
    private function media(): Attribute
    {
        return Attribute::make(get: function ($value) {
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
        });
    }
}
