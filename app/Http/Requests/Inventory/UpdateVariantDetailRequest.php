<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVariantDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::INVENTORY_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['nullable', 'string', 'max:50', Rule::unique('product_variants', 'identifier')->ignore($this->route('variant'))],
            'barcode' => ['nullable', 'string', 'max:100'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'margin_type' => ['required', 'in:percent,amount'],
            'margin_value' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'minimum_stock_level' => ['nullable', 'integer', 'min:0'],
            'has_expiration' => ['boolean'],
            'status' => ['required', 'in:active,inactive,archived'],
        ];
    }
}
