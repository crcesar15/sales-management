<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\PurchaseOrders;

use App\Enums\PermissionsEnum;
use App\Http\Requests\PurchaseOrders\Concerns\ValidatesPurchaseDiscounts;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class PreviewPurchaseOrderRequest extends FormRequest
{
    use ValidatesPurchaseDiscounts;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ($this->user()?->can(PermissionsEnum::PURCHASE_ORDERS_CREATE->value) ?? false)
            || ($this->user()?->can(PermissionsEnum::PURCHASE_ORDERS_EDIT->value) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:product_variant_units,id'],
            ...$this->purchaseDiscountRules(),
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(fn ($validator) => $this->validatePurchaseDiscounts($validator));
    }
}
