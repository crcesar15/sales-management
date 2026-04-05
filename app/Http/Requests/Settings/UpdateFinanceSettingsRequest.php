<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateFinanceSettingsRequest extends FormRequest
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
            'currency' => ['required', 'string', 'max:5', Rule::in(['USD', 'EUR', 'MXN', 'COP', 'BOB'])],
            'currency_symbol' => ['required', 'string', 'max:5'],
            'decimal_precision' => ['required', 'integer', 'min:0', 'max:6'],
        ];
    }
}
