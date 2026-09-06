<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Pos;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;

final class ListPosRegistersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::POS_ACCESS->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
