<?php

declare(strict_types=1);

namespace App\Http\Requests\PurchaseOrders\Concerns;

use App\Enums\DiscountType;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderTotalsService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

trait ValidatesPurchaseDiscounts
{
    /** @return array<string, array<mixed>> */
    protected function purchaseDiscountRules(?PurchaseOrder $order = null): array
    {
        $rules = [
            'discount' => ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'discount_type' => ['sometimes', Rule::enum(DiscountType::class)],
            'discount_value' => ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'items.*.discount_type' => ['sometimes', Rule::enum(DiscountType::class)],
            'items.*.discount_value' => ['sometimes', 'nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'items.*.price' => ['required_with:items', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:999999'],
            'items.*.pricing_reference' => ['sometimes', 'boolean'],
        ];
        if ($order !== null) {
            $rules['items.*.id'] = ['sometimes', 'integer', Rule::exists('purchase_order_product', 'id')->where('purchase_order_id', $order->id)];
        }

        return $rules;
    }

    protected function validatePurchaseDiscounts(Validator $validator, ?PurchaseOrder $order = null): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }
        $calculator = app(PurchaseOrderTotalsService::class);
        foreach ($calculator->effectiveItems($this->all(), $order) as $index => $item) {
            try {
                $calculator->line($item);
            } catch (InvalidArgumentException $exception) {
                $validator->errors()->add("items.{$index}.discount_value", $exception->getMessage());
            }
        }
        if ($validator->errors()->isNotEmpty()) {
            return;
        }
        try {
            $calculator->forInput($this->all(), $order);
        } catch (InvalidArgumentException $exception) {
            $validator->errors()->add('discount_value', $exception->getMessage());
        }
    }
}
