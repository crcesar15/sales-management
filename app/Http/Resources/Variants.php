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
                    'categories' => count($variant->product->categories) > 0
                        ? implode(', ', $variant->product->categories->pluck('name')->toArray())
                        : '',
                    'brand' => $variant->product->brand->name,
                    'name' => ($variant->product->name === $variant->name)
                        ? $variant->product->name
                        : $variant->name . ' - ' . $variant->product->name,
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
