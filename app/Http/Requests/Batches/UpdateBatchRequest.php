<?php

declare(strict_types=1);

namespace App\Http\Requests\Batches;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::BATCHES_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'batch_identifier' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date'],
        ];
    }
}
