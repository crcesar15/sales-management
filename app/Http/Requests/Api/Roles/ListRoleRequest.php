<?php

namespace App\Http\Requests\Api\Roles;

use Illuminate\Foundation\Http\FormRequest;

class ListRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('roles-view');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'order_by' => 'sometimes|string|in:name,created_at,updated_at',
            'order_direction' => 'sometimes|string|in:asc,desc',
            'filter' => 'sometimes|string|max:255',
            'include' => 'sometimes|array|in:permissions',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->query('per_page', 10),
            'page' => $this->query('page', 1),
            'order_by' => $this->query('order_by', 'name'),
            'order_direction' => $this->query('order_direction', 'asc'),
            // 'include' => $include,
        ]);

        if ($this->has('filter')) {
            $this->merge([
                'filter' => '%' . $this->query('filter') . '%',
            ]);
        }

        if ($this->has('include')) {
            $this->merge([
                'include' => explode(',', $this->query('include')),
            ]);
        }
    }
}
