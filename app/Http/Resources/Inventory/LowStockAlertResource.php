<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductVariant */
final class LowStockAlertResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProductVariant $variant */
        $variant = $this->resource;

        return [
            'id' => $variant->id,
            'product_id' => $variant->product_id,
            'product_name' => $variant->product?->name,
            'brand_name' => $variant->product?->brand?->name,
            'name' => $variant->name,
            'identifier' => $variant->identifier,
            'barcode' => $variant->barcode,
            'price' => (float) $variant->price,
            'total_stock' => (int) $variant->stock,
            'minimum_stock_level' => $variant->minimum_stock_level,
            'shortage_quantity' => (int) $variant->getAttribute('shortage_quantity'),
            'status' => $variant->status,
            'values' => $variant->values->map(fn ($value) => [
                'id' => $value->id,
                'option_name' => $value->option?->name,
                'value' => $value->value,
            ])->values()->all(),
        ];
    }
}
