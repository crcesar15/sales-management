<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class ProductCollection extends ResourceCollection
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection?->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'status' => $product->status,
                'brand' => $product->brand?->name,
                'categories' => $product->categories->pluck('name')->join(', '),
                'thumb_url' => $product->getFirstMediaUrl('images', 'thumb'),
                'variants_count' => $product->variants_count,
                'deleted_at' => $product->deleted_at?->toISOString(),
                'created_at' => $product->created_at?->toISOString(),
            ]) ?? [],
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'total' => $this->resource->total(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $paginated
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [];
    }
}
