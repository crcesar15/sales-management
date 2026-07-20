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
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.sale_unit_id' => ['nullable', 'integer', 'exists:product_variant_units,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
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
        });
    }
}
