<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use App\Enums\PermissionsEnum;
use App\Models\Catalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function assert;

final class UpdateCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::CATALOG_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $catalog = $this->route('catalog');
        assert($catalog instanceof Catalog);

        return [
            'vendor_id' => [
                'required',
                'exists:vendors,id',
                Rule::unique('catalog')
                    ->where(
                        fn ($query) => $query
                            ->where('vendor_id', $this->input('vendor_id'))
                            ->where('product_variant_id', $this->input('product_variant_id'))
                            ->where('unit_id', $this->input('unit_id'))
                    )
                    ->ignore($catalog->id),
            ],
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'unit_id' => ['nullable', 'exists:product_variant_units,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'payment_terms' => ['nullable', 'string', 'max:15'],
            'details' => ['nullable', 'string', 'max:300'],
            'status' => ['required', 'in:active,inactive'],
            'minimum_order_quantity' => ['nullable', 'integer', 'min:0'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
