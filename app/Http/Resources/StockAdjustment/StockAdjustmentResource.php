<?php

declare(strict_types=1);

namespace App\Http\Resources\StockAdjustment;

use App\Models\StockAdjustment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockAdjustment */
final class StockAdjustmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var StockAdjustment $adjustment */
        $adjustment = $this->resource;

        return [
            'id' => $adjustment->id,
            'product_variant_id' => $adjustment->product_variant_id,
            'store_id' => $adjustment->store_id,
            'batch_id' => $adjustment->batch_id,
            'quantity_change' => $adjustment->quantity_change,
            'reason' => $adjustment->reason,
            'notes' => $adjustment->notes,
            'created_at' => $adjustment->getAttribute('created_at')?->toISOString(),
            'product_variant' => $this->whenLoaded('productVariant', fn () => [
                'id' => $adjustment->productVariant?->id,
                'name' => $adjustment->productVariant?->name,
                'product' => [
                    'id' => $adjustment->productVariant?->product?->id,
                    'name' => $adjustment->productVariant?->product?->name,
                    'brand' => $adjustment->productVariant?->product?->brand?->name,
                ],
            ]),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $adjustment->store?->id,
                'name' => $adjustment->store?->name,
                'code' => $adjustment->store?->code,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $adjustment->user?->id,
                'full_name' => $adjustment->user?->full_name,
            ]),
            'batch' => $this->whenLoaded('batch', fn () => [
                'id' => $adjustment->batch?->id,
                'initial_quantity' => $adjustment->batch?->initial_quantity,
                'remaining_quantity' => $adjustment->batch?->remaining_quantity,
            ]),
        ];
    }
}
