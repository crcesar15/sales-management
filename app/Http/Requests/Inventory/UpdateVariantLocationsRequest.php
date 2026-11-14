<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\PermissionsEnum;
use App\Services\ReportingScopeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateVariantLocationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::INVENTORY_EDIT->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $storeIds = app(ReportingScopeService::class)->storeIds($this->user() ?? abort(401));

        return [
            'locations' => ['required', 'array'],
            'locations.*' => ['required', 'array:store_id,location_code'],
            'locations.*.store_id' => ['required', 'integer', 'distinct', Rule::in($storeIds), Rule::exists('stores', 'id')->where('status', 'active')->whereNull('deleted_at')],
            'locations.*.location_code' => ['present', 'nullable', 'string', 'max:50'],
            'replicate_locations' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $locations = $this->input('locations');
        if (is_array($locations)) {
            foreach ($locations as &$location) {
                if (is_array($location) && isset($location['location_code']) && is_string($location['location_code'])) {
                    $normalized = mb_strtoupper(mb_trim($location['location_code']));
                    $location['location_code'] = $normalized === '' ? null : $normalized;
                }
            }
            $this->merge(['locations' => $locations]);
        }
    }
}
