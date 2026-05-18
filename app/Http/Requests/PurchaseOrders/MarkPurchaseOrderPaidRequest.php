<?php

declare(strict_types=1);

namespace App\Http\Requests\PurchaseOrders;

use App\Enums\PaymentMethod;
use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class MarkPurchaseOrderPaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::PURCHASE_ORDERS_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'proof_of_payment_type' => ['required', 'string', new Enum(PaymentMethod::class)],
            'proof_of_payment_number' => ['nullable', 'string', 'max:100'],
            'proof_of_payment_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
