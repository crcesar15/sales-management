<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateTransferStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::STOCK_TRANSFERS_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:picked,in_transit,received,completed'],
            'items' => ['nullable', 'array', 'required_if:status,picked,received'],
            'items.*.id' => ['required_with:items', 'integer', 'distinct:strict', 'exists:stock_transfer_items,id'],
            'items.*.quantity_sent' => ['required_if:status,picked', 'nullable', 'integer', 'min:0'],
            'items.*.quantity_received' => ['required_if:status,received', 'nullable', 'integer', 'min:0'],
        ];
    }
}
