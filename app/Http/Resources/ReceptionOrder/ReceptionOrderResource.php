<?php

declare(strict_types=1);

namespace App\Http\Resources\ReceptionOrder;

use App\Models\ReceptionOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReceptionOrder */
final class ReceptionOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ReceptionOrder $reception */
        $reception = $this->resource;

        return [
            'id' => $reception->id,
            'purchase_order_id' => $reception->purchase_order_id,
            'user_id' => $reception->user_id,
            'vendor_id' => $reception->vendor_id,
            'store_id' => $reception->store_id,
            'reception_date' => $reception->getAttribute('reception_date')?->toDateString(),
            'notes' => $reception->notes,
            'status' => $reception->status,
            'purchaseOrder' => $this->whenLoaded('purchaseOrder', fn () => [
                'id' => $reception->purchaseOrder?->id,
                'status' => $reception->purchaseOrder?->status,
                'order_date' => $reception->purchaseOrder?->getAttribute('order_date')?->toDateString(),
                'total' => $reception->purchaseOrder?->total !== null ? (float) $reception->purchaseOrder->total : null,
                'vendor' => $reception->purchaseOrder?->relationLoaded('vendor') && $reception->purchaseOrder->vendor !== null ? [
                    'id' => $reception->purchaseOrder->vendor->id,
                    'fullname' => $reception->purchaseOrder->vendor->fullname,
                ] : null,
                'line_items' => $reception->purchaseOrder?->relationLoaded('lineItems') ? $reception->purchaseOrder->lineItems->map(fn ($item) => [
                    'id' => $item->id,
                    'purchase_order_id' => $item->purchase_order_id,
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => (float) $item->quantity,
                    'received_quantity' => (float) $item->received_quantity,
                    'remaining_quantity' => (float) $item->remaining_quantity,
                    'price' => (float) $item->price,
                    'total' => (float) $item->total,
                    'product_variant' => $item->productVariant ? [
                        'id' => $item->productVariant->id,
                        'name' => $item->productVariant->name,
                        'identifier' => $item->productVariant->identifier,
                        'product' => $item->productVariant->product ? [
                            'id' => $item->productVariant->product->id,
                            'name' => $item->productVariant->product->name,
                        ] : null,
                    ] : null,
                ]) : null,
            ]),
            'vendor' => $this->whenLoaded('vendor', fn () => [
                'id' => $reception->vendor?->id,
                'fullname' => $reception->vendor?->fullname,
                'email' => $reception->vendor?->email,
                'phone' => $reception->vendor?->phone,
                'address' => $reception->vendor?->address,
                'details' => $reception->vendor?->details,
                'additional_contacts' => $reception->vendor?->additional_contacts,
            ]),
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $reception->store?->id,
                'name' => $reception->store?->name,
                'code' => $reception->store?->code,
            ]),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $reception->user?->id,
                'full_name' => $reception->user?->full_name,
            ]),
            'lineItems' => $this->whenLoaded('lineItems') ? $reception->lineItems->map(fn ($item) => [
                'id' => $item->id,
                'reception_order_id' => $item->reception_order_id,
                'purchase_order_item_id' => $item->purchase_order_item_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity' => (float) $item->quantity,
                'price' => (float) $item->price,
                'total' => (float) $item->total,
                'expiry_date' => $item->getAttribute('expiry_date')?->toDateString(),
                'product_variant' => $item->productVariant ? [
                    'id' => $item->productVariant->id,
                    'name' => $item->productVariant->name,
                    'identifier' => $item->productVariant->identifier,
                    'stock' => $item->productVariant->stock,
                    'minimum_stock_level' => $item->productVariant->minimum_stock_level,
                    'product' => $item->productVariant->product ? [
                        'id' => $item->productVariant->product->id,
                        'name' => $item->productVariant->product->name,
                        'measurement_unit' => $item->productVariant->product->relationLoaded('measurementUnit') && $item->productVariant->product->measurementUnit !== null ? [
                            'id' => $item->productVariant->product->measurementUnit->id,
                            'name' => $item->productVariant->product->measurementUnit->name,
                            'abbreviation' => $item->productVariant->product->measurementUnit->abbreviation,
                        ] : null,
                    ] : null,
                ] : null,
                'catalog_entry' => $item->relationLoaded('catalogEntry') && $item->catalogEntry !== null ? [
                    'id' => $item->catalogEntry->id,
                    'price' => (float) $item->catalogEntry->price,
                    'unit_id' => $item->catalogEntry->unit_id,
                    'unit' => $item->catalogEntry->relationLoaded('unit') && $item->catalogEntry->unit !== null ? [
                        'id' => $item->catalogEntry->unit->id,
                        'name' => $item->catalogEntry->unit->name,
                        'conversion_factor' => (float) $item->catalogEntry->unit->conversion_factor,
                    ] : null,
                    'payment_terms' => $item->catalogEntry->payment_terms,
                    'minimum_order_quantity' => $item->catalogEntry->minimum_order_quantity,
                    'lead_time_days' => $item->catalogEntry->lead_time_days,
                    'details' => $item->catalogEntry->details,
                ] : null,
            ]) : [],
            'created_at' => $reception->created_at?->toISOString(),
            'updated_at' => $reception->updated_at?->toISOString(),
        ];
    }
}
