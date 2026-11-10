<?php

declare(strict_types=1);

namespace App\Http\Resources\Batches;

use App\Models\Batch;
use App\Services\ReportingScopeService;
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
            'unit_cost' => $this->when($request->user() !== null && app(ReportingScopeService::class)->canViewCost($request->user(), $batch->store_id), fn () => $batch->unit_cost),
            'batch_identifier' => $batch->batch_identifier,
            'status' => $batch->status,
            'product' => $this->whenLoaded('productVariant', fn () => [
                'name' => $batch->productVariant?->product?->name,
                'brand_name' => $batch->productVariant?->product?->brand?->name,
            ]),
            'product_variant' => $this->whenLoaded('productVariant', fn () => [
                'id' => $batch->productVariant?->id,
                'label' => $batch->productVariant?->name,
                'has_expiration' => $batch->productVariant?->has_expiration,
            ]),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $batch->store?->id,
                'name' => $batch->store?->name,
            ]),
            'reception_order_id' => $batch->reception_order_id,
            'reception_order' => $this->whenLoaded('receptionOrder', fn () => [
                'id' => $batch->receptionOrder?->id,
                'reception_date' => $batch->receptionOrder?->reception_date?->toDateString(),
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
