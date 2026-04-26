<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProductVariant */
final class StockVariantDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{variant: \App\Models\ProductVariant, stores: \Illuminate\Support\Collection<int, array{store: \App\Models\Store|null, quantity: int}>, total_quantity: int} $resource */
        $resource = $this->resource;

        return [
            'variant' => [
                'id' => $resource['variant']->id,
                'product_id' => $resource['variant']->product_id,
                'product_name' => $resource['variant']->product?->name,
                'brand_name' => $resource['variant']->product?->brand?->name,
                'name' => $resource['variant']->name,
                'identifier' => $resource['variant']->identifier,
                'barcode' => $resource['variant']->barcode,
                'price' => (float) $resource['variant']->price,
                'status' => $resource['variant']->status,
                'total_stock' => $resource['total_quantity'],
                'is_low_stock' => $resource['total_quantity'] <= 0,
                'values' => $resource['variant']->values->map(fn ($v) => [
                    'id' => $v->id,
                    'value' => $v->value,
                    'option_name' => $v->option?->name,
                ]),
            ],
            'stores' => $resource['stores']->map(fn ($row) => [
                'store_id' => $row['store']?->id,
                'store_name' => $row['store']?->name,
                'store_code' => $row['store']?->code,
                'quantity' => $row['quantity'],
            ]),
        ];
    }
}
