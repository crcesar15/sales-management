<?php

declare(strict_types=1);

namespace App\Http\Requests\Vendors;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::VENDORS_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fullname' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('vendors', 'email')->ignore($this->route('vendor')),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'details' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'string', 'in:active,inactive,archived'],
            'additional_contacts' => ['nullable', 'array'],
            'additional_contacts.*.name' => ['required_with:additional_contacts', 'string', 'max:255'],
            'additional_contacts.*.phone' => ['nullable', 'string', 'max:50'],
            'additional_contacts.*.email' => ['nullable', 'email', 'max:255'],
            'additional_contacts.*.role' => ['nullable', 'string', 'max:100'],
            'meta' => ['nullable', 'array'],
        ];
    }
}
