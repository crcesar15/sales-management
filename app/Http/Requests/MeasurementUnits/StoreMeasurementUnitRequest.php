<?php

declare(strict_types=1);

namespace App\Http\Requests\MeasurementUnits;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreMeasurementUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::MEASUREMENT_UNITS_CREATE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:measurement_units,name'],
            'abbreviation' => ['required', 'string', 'max:10', 'unique:measurement_units,abbreviation'],
        ];
    }
}
