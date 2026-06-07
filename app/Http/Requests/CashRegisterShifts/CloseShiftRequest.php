<?php

declare(strict_types=1);

namespace App\Http\Requests\CashRegisterShifts;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class CloseShiftRequest extends FormRequest
{
    /**
     * Authorization is handled in the controller based on shift ownership.
     * If the user is not the shift opener, they need shift.manage permission.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'closing_balance' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
