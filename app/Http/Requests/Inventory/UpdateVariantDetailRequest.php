<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\PermissionsEnum;
use App\Models\ProductVariant;
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
        $variant = $this->route('variant');
        abort_unless($variant instanceof ProductVariant, 404);

        $overview = [
            'identifier' => ['nullable', 'string', 'max:50', Rule::unique('product_variants', 'identifier')->ignore($this->route('variant'))],
            'barcode' => ['nullable', 'string', 'max:100'],
            'minimum_stock_level' => ['nullable', 'integer', 'min:0'],
            'has_expiration' => ['boolean'],
            'status' => ['required', 'in:active,inactive,archived'],
            'replicate_inventory_policy' => ['sometimes', 'boolean'],
        ];
        $pricing = [
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'pricing_catalog_id' => ['sometimes', 'nullable', 'integer', Rule::exists('catalog', 'id')->where('product_variant_id', $variant->id)],
            'reference_id' => ['sometimes', 'nullable', 'integer'],
            'margin_type' => ['required', 'in:percent,amount'],
            'margin_value' => ['nullable', 'numeric', 'min:0', ...($this->input('margin_type') === 'percent' ? ['lt:100'] : [])],
            'price' => ['required', 'numeric', 'min:0'],
            'replicate_pricing' => ['sometimes', 'boolean'],
        ];

        return [
            'section' => ['sometimes', 'in:overview,pricing'],
            ...match ($this->input('section')) {
                'overview' => $overview,
                'pricing' => $pricing,
                default => [...$overview, ...$pricing],
            },
        ];
    }
}
