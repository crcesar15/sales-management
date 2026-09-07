<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Pos;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ClosePosShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can(PermissionsEnum::POS_ACCESS->value) ?? false)
            && $this->user()->can(PermissionsEnum::SHIFTS_CLOSE->value);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string'],
            'discrepancy_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
