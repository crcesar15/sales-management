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
                'brand' => $product->brand ? ['id' => $product->brand->id, 'name' => $product->brand->name] : null,
                'categories' => $product->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
                'media' => $product->getMedia('images')->map(fn ($m) => [
                    'id' => $m->id,
                    'thumb_url' => $m->getUrl('thumb'),
                    'full_url' => $m->getUrl(),
                ]),
                'variants' => $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'identifier' => $v->identifier,
                    'name' => $v->name,
                    'option_values' => $v->values->map(fn ($val) => [
                        'option_name' => $val->option?->name,
                        'value' => $val->value,
                    ]),
                    'status' => $v->status,
                    'price' => (float) $v->price,
                    'stock' => $v->stock,
                ]),
                'variants_count' => $product->variants_count,
                'stock' => (int) $product->stock,
                'price_min' => $product->price_min !== null ? (float) $product->price_min : null,
                'price_max' => $product->price_max !== null ? (float) $product->price_max : null,
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
