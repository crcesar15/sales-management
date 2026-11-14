<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Inventory;

use App\Enums\PermissionsEnum;
use App\Models\Store;
use App\Services\ReportingScopeService;
use Illuminate\Foundation\Http\FormRequest;

final class LocationCodeSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $store = $this->route('store');

        return $user !== null && $user->can(PermissionsEnum::INVENTORY_EDIT->value)
            && $store instanceof Store && $store->status === 'active' && ! $store->trashed()
            && in_array($store->id, app(ReportingScopeService::class)->storeIds($user), true);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:50']];
    }
}
