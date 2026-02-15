<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\ActivityLogs;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;

final class ListActivityLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::ACTIVITY_LOGS_VIEW->value) ?? false;
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
            'order_by' => ['sometimes', 'string', 'in:created_at,event,log_name'],
            'order_direction' => ['sometimes', 'string', 'in:asc,desc'],
            'filter' => ['sometimes', 'string', 'max:255'],
            'event' => ['sometimes', 'string', 'in:created,updated,deleted,restored'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->integer('per_page', 15),
            'page' => $this->integer('page', 1),
            'order_by' => $this->string('order_by', 'created_at')->value(),
            'order_direction' => $this->string('order_direction', 'desc')->value(),
        ]);
    }
}
