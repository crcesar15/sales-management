<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Enums\PermissionsEnum;
use App\Models\Batch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class CreateTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::STOCK_TRANSFERS_CREATE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_store_id' => ['required', 'integer', Rule::exists('stores', 'id')->where('status', 'active'), 'different:to_store_id'],
            'to_store_id' => ['required', 'integer', Rule::exists('stores', 'id')->where('status', 'active')],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'distinct:strict', 'exists:product_variants,id'],
            'items.*.quantity_requested' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $fromStoreId = $this->integer('from_store_id');
            foreach ($this->array('items') as $index => $item) {
                $variantId = (int) ($item['product_variant_id'] ?? 0);
                $requestedQuantity = (int) ($item['quantity_requested'] ?? 0);
                $availableQuantity = Batch::query()
                    ->where('product_variant_id', $variantId)
                    ->fulfillableAt($fromStoreId)
                    ->sum('remaining_quantity');

                if ($requestedQuantity > $availableQuantity) {
                    $validator->errors()->add(
                        "items.{$index}.quantity_requested",
                        "The requested quantity exceeds the available stock ({$availableQuantity}).",
                    );
                }
            }
        });
    }
}
