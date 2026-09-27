<?php

declare(strict_types=1);

namespace App\Http\Requests\PurchaseOrders;

use App\Enums\PermissionsEnum;
use App\Http\Requests\PurchaseOrders\Concerns\ValidatesPurchaseOrderItems;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StorePurchaseOrderRequest extends FormRequest
{
    use ValidatesPurchaseOrderItems;

    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::PURCHASE_ORDERS_CREATE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'order_date' => ['required', 'date'],
            'expected_arrival_date' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.catalog_id' => ['required', 'integer', 'exists:catalog,id'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:product_variant_units,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $vendorId = $this->integer('vendor_id');
            $items = $this->array('items');

            if ($vendorId === 0 || $items === []) {
                return;
            }

            $this->validatePurchaseOrderItems($validator, $vendorId, $items);

            $discount = $this->input('discount');
            if ($discount !== null && $discount > 0) {
                $subTotal = 0;
                foreach ($items as $item) {
                    $subTotal += (float) $item['price'] * (float) $item['quantity'];
                }

                if ((float) $discount > $subTotal) {
                    $validator->errors()->add('discount', 'Discount cannot exceed the sub total.');
                }
            }
        });
    }
}
