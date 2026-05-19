<?php

declare(strict_types=1);

namespace App\Http\Requests\ReceptionOrders;

use App\Enums\PermissionsEnum;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreReceptionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::RECEPTION_ORDERS_CREATE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'reception_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'integer', 'exists:purchase_order_product,id'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.expiry_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $purchaseOrderId = $this->integer('purchase_order_id');
            $items = $this->array('items');

            if ($purchaseOrderId === 0 || $items === []) {
                return;
            }

            $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

            if ($purchaseOrder === null) {
                return;
            }

            $blockedStatuses = ['draft', 'awaiting_approval', 'cancelled', 'received'];

            if (in_array($purchaseOrder->status, $blockedStatuses, true)) {
                $validator->errors()->add(
                    'purchase_order_id',
                    "Cannot create a reception order against a purchase order with status: {$purchaseOrder->status}.",
                );

                return;
            }

            $poItemIds = $purchaseOrder->lineItems->pluck('id')->toArray();
            $itemPoIds = array_map(fn (array $item) => $item['purchase_order_item_id'], $items);

            foreach ($itemPoIds as $itemPoId) {
                if (! in_array($itemPoId, $poItemIds, true)) {
                    $validator->errors()->add(
                        'items',
                        "Purchase order item ID {$itemPoId} is not in purchase order #{$purchaseOrder->id}'s line items.",
                    );
                }
            }

            // Validate that reception quantities don't exceed remaining ordered quantities
            $poLineItems = $purchaseOrder->lineItems->keyBy('id');
            $claimedQuantities = $this->getClaimedQuantities($purchaseOrder);

            foreach ($items as $index => $item) {
                $poItemId = $item['purchase_order_item_id'];
                $poLineItem = $poLineItems->get($poItemId);

                if ($poLineItem === null) {
                    continue;
                }

                $orderedQuantity = (float) $poLineItem->quantity;
                $alreadyClaimed = (float) ($claimedQuantities[$poItemId] ?? 0);
                $proposedQuantity = (float) $item['quantity'];
                $remaining = $orderedQuantity - $alreadyClaimed;

                if ($proposedQuantity > $remaining) {
                    $validator->errors()->add(
                        "items.{$index}.quantity",
                        "Cannot receive more than the remaining quantity. Ordered: {$orderedQuantity}, already claimed: {$alreadyClaimed}, remaining: {$remaining}.",
                    );
                }
            }
        });
    }

    /**
     * Get the total claimed quantities per PO line item from all non-cancelled reception orders for this PO.
     *
     * @return array<int, string> keyed by purchase_order_item_id
     */
    private function getClaimedQuantities(PurchaseOrder $po): array
    {
        $quantities = [];

        foreach ($po->receptionOrders()->where('status', '!=', 'cancelled')->with('lineItems')->get() as $receptionOrder) {
            foreach ($receptionOrder->lineItems as $lineItem) {
                $poItemId = (int) $lineItem->purchase_order_item_id;
                $quantities[$poItemId] = bcadd((string) ($quantities[$poItemId] ?? '0'), (string) $lineItem->quantity, 4);
            }
        }

        return $quantities;
    }
}
