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
                    'categories' => $variant->product->categories->pluck('name')->join(', '),
                    'brand' => $variant->product->brand->name,
                    'name' => $variant->product->name,
                    'variant' => ($variant->product->name === $variant->name)
                        ? null
                        : $variant->name,
                    'status' => $variant->status,
                    'media' => $variant->media ?? [],
                    'vendors' => $variant->vendors ?? [],
                ];

                return $formattedProduct;
            }),
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}
