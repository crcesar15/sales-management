<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\MeasurementUnits;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;

final class StoreMeasurementUnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::MEASUREMENT_UNITS_CREATE->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:measurement_units,name'],
            'abbreviation' => ['required', 'string', 'max:10', 'unique:measurement_units,abbreviation'],
        ];
    }
}
