<?php

declare(strict_types=1);

namespace App\Http\Requests\MeasurementUnits;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMeasurementUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::MEASUREMENT_UNITS_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('measurement_units', 'name')->ignore($this->route('measurementUnit')),
            ],
            'abbreviation' => [
                'required', 'string', 'max:10',
                Rule::unique('measurement_units', 'abbreviation')->ignore($this->route('measurementUnit')),
            ],
        ];
    }
}
