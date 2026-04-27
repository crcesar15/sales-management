<?php

declare(strict_types=1);

namespace App\Http\Resources\StockTransfer;

use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockTransfer */
final class StockTransferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var StockTransfer $transfer */
        $transfer = $this->resource;

        return [
            'id' => $transfer->id,
            'status' => $transfer->status,
            'notes' => $transfer->notes,
            'cancelled_at' => $transfer->cancelled_at?->toISOString(),
            'completed_at' => $transfer->completed_at?->toISOString(),
            'created_at' => $transfer->getAttribute('created_at')?->toISOString(),
            'from_store_id' => $transfer->from_store_id,
            'to_store_id' => $transfer->to_store_id,
            'from_store' => $this->whenLoaded('fromStore', fn () => [
                'id' => $transfer->fromStore?->id,
                'name' => $transfer->fromStore?->name,
                'code' => $transfer->fromStore?->code,
            ]),
            'to_store' => $this->whenLoaded('toStore', fn () => [
                'id' => $transfer->toStore?->id,
                'name' => $transfer->toStore?->name,
                'code' => $transfer->toStore?->code,
            ]),
            'requested_by_user' => $this->whenLoaded('requestedBy', fn () => [
                'id' => $transfer->requestedBy?->id,
                'full_name' => $transfer->requestedBy?->full_name,
            ]),
            'items' => $this->whenLoaded('items', function () use ($transfer): array {
                return $transfer->items->map(function ($item): array {
                    return [
                        'id' => $item->id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity_requested' => $item->quantity_requested,
                        'quantity_sent' => $item->quantity_sent,
                        'quantity_received' => $item->quantity_received,
                        'product_variant' => [
                            'id' => $item->productVariant?->id,
                            'name' => $item->productVariant?->name,
                            'sku' => $item->productVariant?->sku,
                            'product' => [
                                'id' => $item->productVariant?->product?->id,
                                'name' => $item->productVariant?->product?->name,
                            ],
                        ],
                    ];
                })->toArray();
            }),
        ];
    }
}
