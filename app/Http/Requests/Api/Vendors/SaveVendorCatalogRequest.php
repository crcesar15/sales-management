<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Vendors;

use App\Enums\PermissionsEnum;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class SaveVendorCatalogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can($this->route('variant') === null ? PermissionsEnum::CATALOG_EDIT->value : PermissionsEnum::CATALOG_CREATE->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $prefix = $this->route('variant') === null ? 'variants.*.' : 'record.';

        return [
            $this->route('variant') === null ? 'variants' : 'record' => ['present', 'array'],
            ...($this->route('variant') === null ? ['variants' => ['present', 'array', 'max:100']] : ['record.previous_product_id' => ['nullable', 'integer', 'exists:product_variants,id']]),
            ...($this->route('variant') === null ? ['variants.*.id' => ['required', 'integer', 'distinct', 'exists:product_variants,id']] : []),
            $prefix . 'price' => ['required', 'numeric', 'min:0'],
            $prefix . 'unit_id' => ['nullable', 'integer', 'exists:product_variant_units,id'],
            $prefix . 'payment_terms' => ['nullable', 'in:immediate,credit,both'],
            $prefix . 'details' => ['nullable', 'string', 'max:300'],
            $prefix . 'status' => ['sometimes', 'in:active,inactive'],
            $prefix . 'minimum_order_quantity' => ['nullable', 'integer', 'min:0'],
            $prefix . 'lead_time_days' => ['nullable', 'integer', 'min:0'],
            $prefix . 'is_outdated' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $records = $this->route('variant') === null ? $this->array('variants') : [$this->array('record')];
            foreach ($records as $record) {
                if (! isset($record['unit_id'])) {
                    continue;
                }
                $variant = $this->route('variant');
                $variantId = $record['id'] ?? ($variant instanceof ProductVariant ? $variant->id : null);
                if (! ProductVariantUnit::query()->whereKey($record['unit_id'])->where('product_variant_id', $variantId)->where('type', 'purchase')->where('status', 'active')->exists()) {
                    $validator->errors()->add('unit_id', 'Select a valid purchase unit for this product.');
                }
            }
        });
    }
}
