<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::POS_ACCESS->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'is_walk_in' => ['required', 'boolean'],
            'discount_type' => ['required', 'string', 'in:flat,percentage'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.sale_unit_id' => ['nullable', 'integer', 'exists:product_variant_units,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /** @param \Illuminate\Validation\Validator $validator */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('customer_id') === null && ! $this->boolean('is_walk_in')) {
                $validator->errors()->add('customer_id', __('Select a customer or mark the sale as walk-in'));
            }

            if ($this->string('discount_type')->toString() === 'percentage' && $this->float('discount_value') > 100) {
                $validator->errors()->add('discount_value', __('The discount percentage may not be greater than 100.'));
            }
        });
    }
}
