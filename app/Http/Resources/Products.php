<?php

namespace App\Http\Resources;

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
                $formattedProduct = [
                    'id' => $product->id,
                    'category' => $product->category,
                    'brand' => $product->brand,
                    'measure_unit' => $product->measure_unit,
                    'name' => $product->name,
                    'options' => $product->options,
                    'status' => $product->status,
                    'price' => $product->variants->min('price'),
                    'stock' => $product->variants->sum('stock'),
                ];

                $variants = $product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'identifier' => $variant->identifier,
                        'name' => $variant->name,
                        'description' => $variant->description,
                        'price' => $variant->price,
                        'stock' => $variant->stock,
                        'status' => $variant->status,
                        'media' => $variant->media->map(function ($media) {
                            return [
                                'id' => $media->id,
                                'url' => $media->url,
                                'type' => $media->model_type,
                            ];
                        }),
                    ];
                });

                $formattedProduct['variants'] = $variants;

                // Check if the product has media
                if ($product->media->isNotEmpty()) {
                    $formattedProduct['media'] = $product->media->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'url' => $media->url,
                            'type' => $media->model_type,
                        ];
                    });
                } else {
                    if (count($variants[0]['media']) > 0) {
                        $formattedProduct['media'] = $variants[0]['media'];
                    } else {
                        $formattedProduct['media'] = [];
                    }
                }

                return $formattedProduct;
            }),
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}
