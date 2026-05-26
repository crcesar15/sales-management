<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::PRODUCTS_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['sometimes', 'nullable', 'string', 'max:50', Rule::unique('product_variants', 'identifier')->ignore($this->route('variant'))],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:50'],
            'purchase_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'margin_type' => ['sometimes', 'in:percent,amount'],
            'margin_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'minimum_stock_level' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'has_expiration' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:active,inactive,archived'],
        ];
    }
}
