<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::PRODUCTS_CREATE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:350'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'measurement_unit_id' => ['nullable', 'exists:measurement_units,id'],
            'status' => ['required', 'in:active,inactive,archived'],
            'categories_ids' => ['nullable', 'array'],
            'categories_ids.*' => ['exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'pending_media_ids' => ['nullable', 'array'],
            'pending_media_ids.*' => ['exists:pending_media_uploads,id,user_id,' . $this->user()?->id],
        ];
    }
}
