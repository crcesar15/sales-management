<?php

declare(strict_types=1);

namespace App\Http\Requests\Preferences;

use App\Enums\UserLocale;
use App\Enums\UserTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<\Illuminate\Validation\Rules\Enum|string>>
     */
    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'required', Rule::enum(UserLocale::class)],
            'theme' => ['sometimes', 'required', Rule::enum(UserTheme::class)],
        ];
    }
}
