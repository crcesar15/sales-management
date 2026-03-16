<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Users;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::USERS_EDIT->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($this->route('user')),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'string', 'in:active,inactive,archived'],
            'date_of_birth' => ['nullable', 'date'],
            'additional_properties' => ['nullable', 'array'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required_with:password', 'string', 'min:8'],
            'role' => ['sometimes', 'string', 'exists:roles,name'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['sometimes', 'exists:roles,id'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['exists:stores,id'],
        ];
    }
}
