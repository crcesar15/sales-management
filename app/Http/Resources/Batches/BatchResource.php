<?php

declare(strict_types=1);

namespace App\Http\Resources\Batches;

use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Batch */
final class BatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Batch $batch */
        $batch = $this->resource;

        return [
            'id' => $batch->id,
            'status' => $batch->status,
            'product_variant' => $this->whenLoaded('productVariant', fn () => [
                'id' => $batch->productVariant?->id,
                'label' => $batch->productVariant?->name,
                'product_name' => $batch->productVariant?->product?->name,
            ]),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $batch->store?->id,
                'name' => $batch->store?->name,
            ]),
            'reception_order_id' => $batch->reception_order_id,
            'reception_order' => $this->whenLoaded('receptionOrder', fn () => [
                'id' => $batch->receptionOrder?->id,
                'reception_date' => $batch->receptionOrder?->reception_date,
            ]),
            'expiry_date' => $batch->getAttribute('expiry_date')?->toDateString(),
            'expiry_status' => $batch->expiry_status,
            'initial_quantity' => $batch->initial_quantity,
            'remaining_quantity' => $batch->remaining_quantity,
            'sold_quantity' => $batch->sold_quantity,
            'transferred_quantity' => $batch->transferred_quantity,
            'missing_quantity' => $batch->missing_quantity,
            'created_at' => $batch->created_at?->toISOString(),
        ];
    }
}
