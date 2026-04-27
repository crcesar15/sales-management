<?php

declare(strict_types=1);

namespace App\Http\Resources\StockTransfer;

use App\Models\StockTransferItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockTransferItem */
final class StockTransferItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var StockTransferItem $item */
        $item = $this->resource;

        return [
            'id' => $item->id,
            'product_variant_id' => $item->product_variant_id,
            'quantity_requested' => $item->quantity_requested,
            'quantity_sent' => $item->quantity_sent,
            'quantity_received' => $item->quantity_received,
            'product_variant' => $this->whenLoaded('productVariant', fn () => [
                'id' => $item->productVariant?->id,
                'label' => $item->productVariant?->name,
                'product_name' => $item->productVariant?->product?->name,
            ]),
        ];
    }
}
