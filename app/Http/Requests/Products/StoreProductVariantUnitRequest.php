<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\PermissionsEnum;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function assert;

final class StoreProductVariantUnitRequest extends FormRequest
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
        $variant = $this->route('variant');
        assert($variant instanceof ProductVariant);
        $type = $this->input('type');

        return [
            'type' => ['required', 'in:sale,purchase'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_variant_units', 'name')
                    ->where('product_variant_id', $variant->id)
                    ->where('type', $type),
            ],
            'conversion_factor' => ['required', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0', Rule::requiredIf($type === 'sale')],
            'status' => ['required', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('type') === 'purchase') {
            $this->merge(['price' => null]);
        }
    }
}
