<?php

declare(strict_types=1);

namespace App\Http\Requests\ReceptionOrders;

use App\Enums\PermissionsEnum;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreReceptionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::RECEPTION_ORDERS_CREATE->value) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'reception_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.purchase_order_item_id' => ['required', 'integer', 'distinct', 'exists:purchase_order_product,id'],
            'items.*.batches' => ['required', 'array', 'min:1'],
            'items.*.batches.*.purchase_unit_quantity' => ['required', 'integer', 'min:0'],
            'items.*.batches.*.loose_unit_quantity' => ['required', 'integer', 'min:0'],
            'items.*.batches.*.expiry_date' => ['nullable', 'date'],
            'items.*.batches.*.batch_identifier' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $purchaseOrder = PurchaseOrder::query()->with('lineItems.unit', 'lineItems.productVariant')->find($this->integer('purchase_order_id'));
            if ($purchaseOrder === null) {
                return;
            }
            if (! in_array($purchaseOrder->status, ['sent', 'partially_received'], true)) {
                $validator->errors()->add('purchase_order_id', 'The purchase order is not available for reception.');

                return;
            }
            foreach ($this->array('items') as $itemIndex => $item) {
                $poLineItem = $purchaseOrder->lineItems->firstWhere('id', $item['purchase_order_item_id']);
                if ($poLineItem === null) {
                    $validator->errors()->add("items.{$itemIndex}.purchase_order_item_id", 'The selected item does not belong to this purchase order.');

                    continue;
                }
                $unit = $poLineItem->unit;
                $conversionFactor = $unit === null ? 1 : $unit->conversion_factor;
                $baseQuantity = 0;
                foreach ($item['batches'] as $batchIndex => $batch) {
                    $purchaseUnits = (int) $batch['purchase_unit_quantity'];
                    $looseUnits = (int) $batch['loose_unit_quantity'];
                    if ($looseUnits >= $conversionFactor) {
                        $validator->errors()->add("items.{$itemIndex}.batches.{$batchIndex}.loose_unit_quantity", 'Loose units must be less than the purchase unit conversion factor.');
                    }
                    if ($purchaseUnits === 0 && $looseUnits === 0) {
                        $validator->errors()->add("items.{$itemIndex}.batches.{$batchIndex}.purchase_unit_quantity", 'A batch must contain a quantity.');
                    }
                    if ($poLineItem->productVariant !== null && $poLineItem->productVariant->has_expiration && empty($batch['expiry_date'])) {
                        $validator->errors()->add("items.{$itemIndex}.batches.{$batchIndex}.expiry_date", 'The expiry date is required for this product variant.');
                    }
                    $baseQuantity += ($purchaseUnits * $conversionFactor) + $looseUnits;
                }
                if ($baseQuantity % $conversionFactor !== 0) {
                    $validator->errors()->add("items.{$itemIndex}.batches", 'Batch allocations must add up to a whole purchase unit quantity.');
                }
            }
        });
    }
}
