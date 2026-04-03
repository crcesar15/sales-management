<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateTaxSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::SETTINGS_MANAGE->value) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
