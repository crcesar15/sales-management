<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProductOptionRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'values' => ['required', 'array', 'min:1'],
            'values.*' => ['required', 'string', 'max:50'],
        ];
    }
}
