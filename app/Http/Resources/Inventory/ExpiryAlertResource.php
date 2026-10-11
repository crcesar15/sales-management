<?php

declare(strict_types=1);

namespace App\Http\Resources\Inventory;

use App\Models\Batch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Batch */
final class ExpiryAlertResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Batch $batch */
        $batch = $this->resource;
        $variant = $batch->productVariant;
        $product = $variant?->product;
        $brand = $product?->brand;
        $store = $batch->store;

        return [
            'id' => $batch->id,
            'batch_identifier' => $batch->batch_identifier,
            'product_variant_id' => $batch->product_variant_id,
            'store_id' => $batch->store_id,
            'expiry_date' => $batch->expiry_date?->toDateString(),
            'initial_quantity' => $batch->initial_quantity,
            'remaining_quantity' => $batch->remaining_quantity,
            'status' => $batch->status,
            'expiry_status' => $batch->expiry_status,
            'product_variant' => [
                'id' => $variant?->id,
                'name' => $variant?->name,
                'identifier' => $variant?->identifier,
                'product' => [
                    'id' => $product?->id,
                    'name' => $product?->name,
                    'brand' => $brand === null ? null : [
                        'id' => $brand->id,
                        'name' => $brand->name,
                    ],
                ],
            ],
            'store' => $store === null ? null : [
                'id' => $store->id,
                'name' => $store->name,
                'code' => $store->code,
            ],
        ];
    }
}
