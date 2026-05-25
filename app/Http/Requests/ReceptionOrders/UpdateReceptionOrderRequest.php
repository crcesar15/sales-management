<?php

declare(strict_types=1);

namespace App\Http\Requests\ReceptionOrders;

use App\Enums\PermissionsEnum;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateReceptionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::RECEPTION_ORDERS_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['sometimes', 'required', 'integer', 'exists:stores,id'],
            'reception_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required_with:items', 'integer', 'exists:purchase_order_product,id'],
            'items.*.product_variant_id' => ['required_with:items', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.expiry_date' => ['nullable', 'date'],
            'items.*.batch_identifier' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var \App\Models\ReceptionOrder|null $receptionOrder */
            $receptionOrder = $this->route('receptionOrder');

            if ($receptionOrder === null) {
                return;
            }

            if ($receptionOrder->status !== 'pending') {
                $validator->errors()->add(
                    'status',
                    'Only pending reception orders can be updated.',
                );

                return;
            }

            $items = $this->input('items');

            if ($items === null) {
                return;
            }

            $purchaseOrder = $receptionOrder->purchaseOrder;

            if ($purchaseOrder === null) {
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
            // Exclude current reception order's quantities from claimed total
            $poLineItems = $purchaseOrder->lineItems->keyBy('id');
            $claimedQuantities = $this->getClaimedQuantities($purchaseOrder, $receptionOrder->id);

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

            // Validate that expiry_date is required for variants with has_expiration = true
            $variantIds = array_map(fn (array $item) => (int) $item['product_variant_id'], $items);
            $variantIds = array_unique($variantIds);
            $variants = ProductVariant::whereIn('id', $variantIds)->get()->keyBy('id');

            foreach ($items as $index => $item) {
                $variantId = (int) $item['product_variant_id'];
                $variant = $variants->get($variantId);

                if ($variant?->has_expiration && empty($item['expiry_date'])) {
                    $validator->errors()->add(
                        "items.{$index}.expiry_date",
                        'The expiry date is required for this product variant.',
                    );
                }
            }
        });
    }

    /**
     * Get the total claimed quantities per PO line item from all non-cancelled
     * reception orders for this PO, excluding the given reception order.
     *
     * @return array<int, string> keyed by purchase_order_item_id
     */
    private function getClaimedQuantities(PurchaseOrder $po, int $excludeReceptionOrderId): array
    {
        $quantities = [];

        foreach ($po->receptionOrders()->where('status', '!=', 'cancelled')->where('id', '!=', $excludeReceptionOrderId)->with('lineItems')->get() as $receptionOrder) {
            foreach ($receptionOrder->lineItems as $lineItem) {
                $poItemId = (int) $lineItem->purchase_order_item_id;
                $quantities[$poItemId] = bcadd((string) ($quantities[$poItemId] ?? '0'), (string) $lineItem->quantity, 4);
            }
        }

        return $quantities;
    }
}
