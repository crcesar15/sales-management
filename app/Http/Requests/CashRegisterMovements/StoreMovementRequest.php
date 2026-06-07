<?php

declare(strict_types=1);

namespace App\Http\Requests\CashRegisterMovements;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::CASH_MOVEMENTS_CREATE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:cash_in,cash_out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
