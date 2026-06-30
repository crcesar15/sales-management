<?php

declare(strict_types=1);

namespace App\Http\Requests\SalesOrders;

use App\Enums\PermissionsEnum;
use App\Enums\SalesOrderStatus;
use App\Models\SalesOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::SALES_MANAGE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'discount_type' => ['required', 'string', 'in:flat,percentage'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.sale_unit_id' => ['nullable', 'integer', 'exists:product_variant_units,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.conversion_factor' => ['required', 'integer', 'min:1'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.payment_method' => ['required', 'string', 'in:cash,credit_card,qr,transfer'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var SalesOrder|null $salesOrder */
            $salesOrder = $this->route('salesOrder');

            if ($salesOrder === null) {
                return;
            }

            if ($salesOrder->status !== SalesOrderStatus::DRAFT) {
                $validator->errors()->add(
                    'status',
                    'Only draft orders can be updated.'
                );
            }

            $items = $this->array('items');
            $payments = $this->array('payments');

            if ($items === [] || $payments === []) {
                return;
            }

            // Validate that payments total matches the order total
            $subTotal = 0.0;
            foreach ($items as $item) {
                $subTotal += (float) $item['unit_price'] * (int) $item['quantity'];
            }

            $discountValue = (float) $this->input('discount_value', 0);
            $discountType = $this->string('discount_type')->toString();

            if ($discountType === 'flat') {
                $discount = min($discountValue, $subTotal);
            } else {
                $discount = round($subTotal * ($discountValue / 100), 2);
            }

            $taxRate = (float) \App\Models\Setting::get('tax_rate', 0);
            $taxAmount = round(($subTotal - $discount) * ($taxRate / 100), 2);
            $total = round($subTotal - $discount + $taxAmount, 2);

            $paymentsTotal = 0.0;
            foreach ($payments as $payment) {
                $paymentsTotal += (float) $payment['amount'];
            }

            if (abs($paymentsTotal - $total) > 0.01) {
                $validator->errors()->add(
                    'payments',
                    "The total payments amount ({$paymentsTotal}) must equal the order total ({$total})."
                );
            }
        });
    }
}
