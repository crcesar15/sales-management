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
            'data' => $this->collection->map(fn($product): array => [
                'id' => $product->id,
                'categories' => $product->categories,
                'brand' => $product->brand,
                'measure_unit' => $product->measureUnit,
                'name' => $product->name,
                'options' => $product->options,
                'status' => $product->status,
                'price' => $product->variants->min('price'),
                'stock' => $product->variants->sum('stock'),
                'media' => $product->media ?? [],
                'description' => $product->description,
                'variants' => $product->variants,
            ]),
            'links' => [
                'self' => 'link-value',
            ],
        ];
    }
}
