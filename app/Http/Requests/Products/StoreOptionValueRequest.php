<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreOptionValueRequest extends FormRequest
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
            'value' => ['required', 'string', 'max:50'],
        ];
    }
}
