<?php

declare(strict_types=1);

namespace App\Http\Requests\SalesOrders;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;

final class ConfirmSalesOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::SALES_MANAGE->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [

        ];
    }
}
