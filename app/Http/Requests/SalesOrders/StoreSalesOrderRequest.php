<?php

declare(strict_types=1);

namespace App\Http\Requests\SalesOrders;

use App\Enums\PermissionsEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::SALES_MANAGE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.sale_unit_id' => ['nullable', 'integer', 'exists:product_variant_units,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            // Ensure the chosen store is one the actor is assigned to.
            $storeId = $this->integer('store_id', 0);
            $user = $this->user();
            if ($storeId > 0 && $user !== null && ! $user->stores()->where('stores.id', $storeId)->exists()) {
                $validator->errors()->add(
                    'store_id',
                    'The selected store is not available for this user.'
                );
            }

            $items = $this->array('items');
        });
    }
}
