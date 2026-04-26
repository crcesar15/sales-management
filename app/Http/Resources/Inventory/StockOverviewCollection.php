<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class StockOverviewCollection extends ResourceCollection
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection?->map(fn ($variant) => [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'product_name' => $variant->product?->name,
                'brand_name' => $variant->product?->brand?->name,
                'name' => $variant->name,
                'identifier' => $variant->identifier,
                'barcode' => $variant->barcode,
                'price' => (float) $variant->price,
                'status' => $variant->status,
                'total_stock' => (int) ($variant->global_stock ?? 0),
                'is_low_stock' => ($variant->global_stock ?? 0) <= 0,
                'values' => $variant->values->map(fn ($v) => [
                    'id' => $v->id,
                    'value' => $v->value,
                    'option_name' => $v->option?->name,
                ]),
                'images' => $variant->images->map(fn ($img) => [
                    'id' => $img->id,
                    'thumb_url' => $img->getUrl('thumb'),
                    'full_url' => $img->getUrl(),
                ]),
                'created_at' => $variant->created_at->toISOString(),
            ]),
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
