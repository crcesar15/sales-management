<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

final class StoreProductMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user?->can(PermissionsEnum::PRODUCTS_CREATE->value)
            || $user?->can(PermissionsEnum::PRODUCTS_EDIT->value);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(10 * 1024),
            ],
        ];
    }
}
