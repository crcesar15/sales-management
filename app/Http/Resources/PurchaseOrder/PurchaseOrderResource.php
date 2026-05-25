<?php

declare(strict_types=1);

namespace App\Http\Resources\PurchaseOrder;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PurchaseOrder */
final class PurchaseOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var PurchaseOrder $po */
        $po = $this->resource;

        return [
            'id' => $po->id,
            'user_id' => $po->user_id,
            'vendor_id' => $po->vendor_id,
            'status' => $po->status,
            'order_date' => $po->getAttribute('order_date')?->toDateString(),
            'expected_arrival_date' => $po->getAttribute('expected_arrival_date')?->toDateString(),
            'sub_total' => $po->sub_total !== null ? (float) $po->sub_total : null,
            'discount' => $po->discount !== null ? (float) $po->discount : null,
            'total' => $po->total !== null ? (float) $po->total : null,
            'notes' => $po->notes,
            'completion_notes' => $po->completion_notes,
            'is_paid' => $po->is_paid,
            'paid_at' => $po->getAttribute('paid_at')?->toISOString(),
            'proof_of_payment_type' => $po->proof_of_payment_type,
            'proof_of_payment_number' => $po->proof_of_payment_number,
            'is_fully_received' => $po->is_fully_received,
            'user' => $this->whenLoaded('user') && $po->user !== null ? [
                'id' => $po->user->id,
                'full_name' => $po->user->full_name,
            ] : null,
            'vendor' => $this->whenLoaded('vendor') && $po->vendor !== null ? [
                'id' => $po->vendor->id,
                'fullname' => $po->vendor->fullname,
                'email' => $po->vendor->email,
                'phone' => $po->vendor->phone,
                'address' => $po->vendor->address,
            ] : null,
            'line_items' => $this->whenLoaded('lineItems') ? $po->lineItems->map(fn ($item) => [
                'id' => $item->id,
                'purchase_order_id' => $item->purchase_order_id,
                'product_variant_id' => $item->product_variant_id,
                'catalog_id' => $item->catalog_id,
                'unit_id' => $item->unit_id,
                'quantity' => (float) $item->quantity,
                'received_quantity' => (float) $item->received_quantity,
                'remaining_quantity' => (float) $item->remaining_quantity,
                'price' => (float) $item->price,
                'total' => (float) $item->total,
                'product_variant' => $item->productVariant ? [
                    'id' => $item->productVariant->id,
                    'name' => $item->productVariant->name,
                    'identifier' => $item->productVariant->identifier,
                    'has_expiration' => $item->productVariant->has_expiration,
                    'product' => $item->productVariant->product ? [
                        'id' => $item->productVariant->product->id,
                        'name' => $item->productVariant->product->name,
                    ] : null,
                ] : null,
            ]) : [],
            'created_at' => $po->created_at?->toISOString(),
            'updated_at' => $po->updated_at?->toISOString(),
        ];
    }
}
