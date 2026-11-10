<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexStockAlertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::STOCK_ALERTS_VIEW->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'per_store' => ['sometimes', 'boolean'],
            'tab' => ['sometimes', Rule::in(['low-stock', 'expiry'])],
            'low_stock_per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'expiry_per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['low_stock_per_page', 'expiry_per_page'] as $field) {
            if ($this->has($field) && filter_var($this->input($field), FILTER_VALIDATE_INT) !== false) {
                $this->merge([$field => min(100, max(1, $this->integer($field)))]);
            }
        }
    }
}
