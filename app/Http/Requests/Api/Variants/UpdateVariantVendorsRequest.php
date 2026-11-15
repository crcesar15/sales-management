<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Variants;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateVariantVendorsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::CATALOG_EDIT->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vendors' => ['present', 'array', 'max:100'],
            'vendors.*.id' => ['required', 'integer', 'distinct', 'exists:vendors,id'],
            'vendors.*.price' => ['required', 'numeric', 'min:0'],
            'vendors.*.payment_terms' => ['nullable', 'in:immediate,credit,both'],
            'vendors.*.details' => ['nullable', 'string', 'max:300'],
        ];
    }
}
