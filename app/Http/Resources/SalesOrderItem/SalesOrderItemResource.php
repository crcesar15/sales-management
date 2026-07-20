<?php

declare(strict_types=1);

namespace App\Http\Resources\SalesOrderItem;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\SalesOrderItem */
final class SalesOrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'product_variant' => $this->whenLoaded('productVariant', fn () => [
                'id' => $this->productVariant?->id,
                'sku' => $this->productVariant?->identifier,
                'name' => $this->productVariant?->name,
                'product' => $this->productVariant?->product ? [
                    'id' => $this->productVariant->product->id,
                    'name' => $this->productVariant->product->name,
                    'brand' => $this->productVariant->product->brand ? [
                        'id' => $this->productVariant->product->brand->id,
                        'name' => $this->productVariant->product->brand->name,
                    ] : null,
                ] : null,
            ]),
            'sale_unit_id' => $this->sale_unit_id,
            'sale_unit' => $this->whenLoaded('saleUnit', fn () => [
                'id' => $this->saleUnit?->id,
                'name' => $this->saleUnit?->name,
            ]),
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'conversion_factor' => $this->conversion_factor,
            'line_total' => (float) $this->line_total,
            'stock_allocations' => $this->whenLoaded('stockAllocations', fn () => $this->stockAllocations->map(fn ($allocation): array => [
                'batch_id' => $allocation->batch_id,
                'quantity' => $allocation->quantity,
                'batch' => $allocation->relationLoaded('batch') ? [
                    'id' => $allocation->batch?->id,
                    'identifier' => $allocation->batch?->batch_identifier,
                    'expiry_date' => $allocation->batch?->expiry_date?->toDateString(),
                ] : null,
            ])->values()),
        ];
    }
}
