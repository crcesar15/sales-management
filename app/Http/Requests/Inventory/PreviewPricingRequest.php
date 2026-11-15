<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class PreviewPricingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ($this->user()?->can(PermissionsEnum::INVENTORY_VIEW->value) ?? false)
            || ($this->user()?->can(PermissionsEnum::PURCHASE_ORDERS_CREATE->value) ?? false)
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
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:product_variant_units,id'],
            'items.*.price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'items.*.margin_type' => ['sometimes', 'in:percent,amount'],
            'items.*.margin_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'items.*.selling_price' => ['sometimes', 'numeric', 'min:0'],
            'items.*.sale_factor' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
