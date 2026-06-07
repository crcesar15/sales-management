<?php

declare(strict_types=1);

namespace App\Http\Requests\CashRegisters;

use App\Enums\PermissionsEnum;
use App\Models\CashRegister;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::CASH_REGISTERS_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var CashRegister $cashRegister */
        $cashRegister = $this->route('cash_register');

        return [
            'store_id' => ['sometimes', 'exists:stores,id'],
            'name' => ['sometimes', 'string', 'max:100'],
            'code' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('cash_registers')->ignore($cashRegister)->where(fn ($query) => $query->where('store_id', $this->input('store_id', $cashRegister->store_id))),
            ],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
