<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Products;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::PRODUCTS_CREATE->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'measurement_unit_id' => ['required', 'integer', 'exists:measurement_units,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'status' => ['required', 'string', 'in:active,inactive,archived'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'media' => ['sometimes', 'array'],
            'media.*' => ['integer'],
            'variants' => ['sometimes', 'array'],
            'variants.*.identifier' => ['required', 'string', 'max:50'],
            'variants.*.name' => ['required', 'string', 'max:150'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.status' => ['required', 'string', 'in:active,inactive,archived'],
            'variants.*.media' => ['sometimes', 'array'],
            'variants.*.media.*' => ['integer'],
        ];
    }
}
