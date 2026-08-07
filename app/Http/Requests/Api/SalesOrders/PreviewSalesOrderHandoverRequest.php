<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\SalesOrders;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;

final class PreviewSalesOrderHandoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::SALES_MANAGE->value) ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [];
    }
}
