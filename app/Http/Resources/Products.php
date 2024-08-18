<?php

namespace App\Http\Resources;

use App\Models\Media;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class Products extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($product) {
                $productMedia = [];

                $formattedProduct = [
                    'id' => $product->id,
                    'categories' => $product->categories,
                    'brand' => $product->brand,
                    'measure_unit' => $product->measureUnit,
                    'name' => $product->name,
                    'options' => $product->options,
                    'status' => $product->status,
                    'price' => $product->variants->min('price'),
                    'stock' => $product->variants->sum('stock'),
                    'description' => $product->description,
                ];

                $variants = $product->variants->map(function ($variant) {
                    $formattedVariant = [
                        'id' => $variant->id,
                        'identifier' => $variant->identifier,
                        'name' => $variant->name,
                        'description' => $variant->description,
                        'price' => $variant->price,
                        'stock' => $variant->stock,
                        'status' => $variant->status,
                        'media' => $variant->media,
                    ];

                    if (!$variant->media) {
                        $formattedVariant['media'] = [];
                    }

                    return $formattedVariant;
                });

                $productMedia = $variants->map(function ($variant) {
                    return $variant['media'];
                })->flatten();

                $formattedProduct['media'] = $productMedia;
                $formattedProduct['variants'] = $variants;

                return $formattedProduct;
            }),
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}
