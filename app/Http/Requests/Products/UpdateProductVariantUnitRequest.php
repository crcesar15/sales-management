<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\PermissionsEnum;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function assert;

final class UpdateProductVariantUnitRequest extends FormRequest
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
        $unit = $this->route('unit');
        assert($unit instanceof ProductVariantUnit);

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('product_variant_units', 'name')
                    ->where('product_variant_id', $variant->id)
                    ->where('type', $unit->type)
                    ->ignore($unit),
            ],
            'conversion_factor' => ['sometimes', 'required', 'integer', 'min:1'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'required', 'in:active,inactive'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $unit = $this->route('unit');
        assert($unit instanceof ProductVariantUnit);

        if ($unit->type === 'purchase') {
            $this->merge(['price' => null]);
        }
    }
}
