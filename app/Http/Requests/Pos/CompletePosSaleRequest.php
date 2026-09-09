<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class CompletePosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::POS_ACCESS->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_mode' => ['required', 'string', 'in:cash,qr,split'],
            'cash_received' => ['nullable', 'required_if:payment_mode,cash', 'numeric', 'min:0.01'],
            'cash_amount' => ['nullable', 'required_if:payment_mode,split', 'numeric', 'min:0.01'],
            'qr_reference' => ['nullable', 'string', 'max:255'],
            'qr_confirmed' => ['exclude_if:payment_mode,cash', 'required_if:payment_mode,qr,split', 'accepted'],
        ];
    }
}
