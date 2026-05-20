<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Customers;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;

final class ListCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::CUSTOMERS_VIEW->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'order_by' => ['sometimes', 'string', 'in:first_name,last_name,email,phone,tax_id,created_at,updated_at'],
            'order_direction' => ['sometimes', 'string', 'in:asc,desc'],
            'filter' => ['sometimes', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation()
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
    }
}
