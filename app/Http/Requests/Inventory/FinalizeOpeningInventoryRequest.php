<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;

final class FinalizeOpeningInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::STOCK_ADJUST->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
