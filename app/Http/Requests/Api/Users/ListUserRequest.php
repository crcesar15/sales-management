<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Users;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ListUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('users-view') ?? false;
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
            'filter' => ['sometimes', 'string', 'max:255'],
            'include' => ['sometimes', 'array', 'in:roles'],
            'status' => ['sometimes', 'string', 'in:active,inactive,archived'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->integer('per_page', 10),
            'page' => $this->integer('page', 1),
            'order_by' => $this->string('order_by', 'first_name')->value(),
            'order_direction' => $this->string('order_direction', 'asc')->value(),
        ]);

        if ($this->has('filter')) {
            $this->merge([
                'filter' => "%{$this->string('filter')->value()}%",
            ]);
        }

        if ($this->has('include')) {
            $this->merge([
                'include' => explode(',', $this->string('include')->value()),
            ]);
        }

        if ($this->has('status')) {
            if ($this->string('status')->value() === 'all') {
                // remove status from the query
                $this->request->remove('status');
            } else {
                $this->merge([
                    'status' => $this->string('status')->value(),
                ]);
            }
        }
    }
}
