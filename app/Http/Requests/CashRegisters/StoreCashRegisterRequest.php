<?php

declare(strict_types=1);

namespace App\Http\Requests\CashRegisters;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::CASH_REGISTERS_CREATE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['required', 'exists:stores,id'],
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('cash_registers')->where(fn ($query) => $query->where('store_id', $this->input('store_id'))),
            ],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
