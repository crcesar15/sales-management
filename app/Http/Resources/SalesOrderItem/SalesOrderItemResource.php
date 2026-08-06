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
                'identifier' => $this->productVariant?->identifier,
                'sku' => $this->productVariant?->identifier,
                'name' => $this->productVariant?->name,
                'option_values' => $this->productVariant?->relationLoaded('values')
                    ? $this->productVariant->values->pluck('value')->implode(' / ')
                    : null,
                'minimum_stock_level' => $this->productVariant?->minimum_stock_level,
                'product' => $this->productVariant?->product ? [
                    'id' => $this->productVariant->product->id,
                    'name' => $this->productVariant->product->name,
                    'brand' => $this->productVariant->product->brand ? [
                        'id' => $this->productVariant->product->brand->id,
                        'name' => $this->productVariant->product->brand->name,
                    ] : null,
                    'measurement_unit' => $this->productVariant->product->measurementUnit ? [
                        'id' => $this->productVariant->product->measurementUnit->id,
                        'name' => $this->productVariant->product->measurementUnit->name,
                    ] : null,
                ] : null,
            ]),
            'sale_unit_id' => $this->sale_unit_id,
            'sale_unit' => $this->whenLoaded('saleUnit', fn () => [
                'id' => $this->saleUnit?->id,
                'name' => $this->saleUnit?->name,
                'conversion_factor' => $this->saleUnit?->conversion_factor,
            ]),
            'stock' => $this->when(
                $this->relationLoaded('productVariant') && $this->productVariant?->relationLoaded('batches'),
                fn (): int => (int) ($this->productVariant?->batches->sum('remaining_quantity') ?? 0),
            ),
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
