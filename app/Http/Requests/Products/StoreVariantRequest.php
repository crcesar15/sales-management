<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\PermissionsEnum;
use App\Models\Product;
use App\Models\ProductOptionValue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

use function assert;

final class StoreVariantRequest extends FormRequest
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
            'identifier' => ['nullable', 'string', 'max:50', 'unique:product_variants,identifier'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'in:active,inactive,archived'],
            'option_value_ids' => ['required', 'array', 'min:1'],
            'option_value_ids.*' => ['required', 'integer', 'distinct', 'exists:product_option_values,id'],
        ];
    }

    /**
     * Verify all option_value_ids belong to the product's options.
     */
    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $product = $this->route('product');
            assert($product instanceof Product);
            $valueIds = $this->input('option_value_ids', []);

            $optionIds = ProductOptionValue::whereIn('id', $valueIds)
                ->whereHas('option', fn ($q) => $q->where('product_id', $product->id))
                ->pluck('product_option_id')
                ->all();
            $optionCount = $product->options()->count();

            if (count($optionIds) !== count($valueIds)) {
                $validator->errors()->add('option_value_ids', 'All option values must belong to this product.');
            }

            if (count($valueIds) !== $optionCount || count(array_unique($optionIds)) !== $optionCount) {
                $validator->errors()->add('option_value_ids', 'Select exactly one value for each product option.');
            }
        });
    }
}
