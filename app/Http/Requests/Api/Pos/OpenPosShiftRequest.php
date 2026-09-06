<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Pos;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class OpenPosShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can(PermissionsEnum::POS_ACCESS->value) ?? false)
            && $this->user()->can(PermissionsEnum::SHIFTS_OPEN->value);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'register_id' => ['required', 'integer', 'exists:cash_registers,id'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
        ];
    }
}
