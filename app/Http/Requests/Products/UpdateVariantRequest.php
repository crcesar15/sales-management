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
            'status' => ['sometimes', 'in:active,inactive,archived'],
        ];
    }
}
