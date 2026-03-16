<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Users;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ListUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::USERS_VIEW->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'order_by' => ['sometimes', 'string', 'in:first_name,last_name,username,status,created_at,updated_at'],
            'order_direction' => ['sometimes', 'string', 'in:asc,desc'],
            'search' => ['sometimes', 'string', 'max:255'],
            'filter' => ['sometimes', 'string', 'max:255'],
            'include' => ['sometimes', 'array', 'in:roles'],
            'status' => ['sometimes', 'string', 'in:active,inactive,archived'],
            'store_id' => ['sometimes', 'integer', 'exists:stores,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->integer('per_page', 20),
            'page' => $this->integer('page', 1),
            'order_by' => $this->string('order_by', 'first_name')->value(),
            'order_direction' => $this->string('order_direction', 'asc')->value(),
        ]);

        // Normalise: ?search= is the documented param; ?filter= is the legacy alias
        if ($this->has('search') && ! $this->has('filter')) {
            $this->merge(['filter' => $this->string('search')->value()]);
        }

        if ($this->has('include') && is_string($this->input('include'))) {
            $this->merge([
                'include' => explode(',', $this->string('include')->value()),
            ]);
        }

        if ($this->has('status') && $this->string('status')->value() === 'all') {
            $this->request->remove('status');
        }
    }
}
