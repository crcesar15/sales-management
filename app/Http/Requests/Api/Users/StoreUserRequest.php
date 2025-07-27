<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Users;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('users-create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'string', 'in:active,inactive,archived'],
            'date_of_birth' => ['nullable', 'date'],
            'additional_properties' => ['nullable', 'array'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required_with:password', 'string', 'min:8'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['sometimes', 'exists:roles,id'],
        ];
    }
}
