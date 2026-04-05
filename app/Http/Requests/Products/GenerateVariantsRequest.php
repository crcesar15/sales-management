<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class GenerateVariantsRequest extends FormRequest
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
            'options' => ['required', 'array', 'min:1'],
            'options.*.name' => ['required', 'string', 'max:150'],
            'options.*.values' => ['required', 'array', 'min:1'],
            'options.*.values.*' => ['required', 'string', 'max:50'],
        ];
    }
}
