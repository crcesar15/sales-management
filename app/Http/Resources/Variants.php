<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class Variants extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($variant) {
                $formattedProduct = [
                    'id' => $variant->id,
                    'categories' => $variant->categories,
                    'brand' => $variant->brand_name,
                    'name' => $variant->product_name,
                    'variant' => ($variant->product_name === $variant->name)
                        ? null
                        : $variant->name,
                    'status' => $variant->status,
                    'media' => $variant->media ?? [],
                ];

                return $formattedProduct;
            }),
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}
