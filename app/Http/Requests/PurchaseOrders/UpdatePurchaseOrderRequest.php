<?php

declare(strict_types=1);

namespace App\Http\Requests\PurchaseOrders;

use App\Enums\PermissionsEnum;
use App\Models\Catalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::PURCHASE_ORDERS_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vendor_id' => ['sometimes', 'required', 'integer', 'exists:vendors,id'],
            'order_date' => ['sometimes', 'required', 'date'],
            'expected_arrival_date' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required_with:items', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var \App\Models\PurchaseOrder $po */
            $po = $this->route('purchaseOrder');

            if ($po->status !== 'draft') {
                $validator->errors()->add('status', 'Only draft purchase orders can be updated.');

                return;
            }

            $items = $this->array('items');

            if ($items === []) {
                return;
            }

            $vendorId = $this->integer('vendor_id') ?: $po->vendor_id;
            $variantIds = array_map(fn (array $item) => $item['product_variant_id'], $items);

            $activeCatalogVariants = Catalog::query()
                ->where('vendor_id', $vendorId)
                ->where('status', 'active')
                ->whereIn('product_variant_id', $variantIds)
                ->pluck('product_variant_id')
                ->toArray();

            foreach ($variantIds as $variantId) {
                if (! in_array($variantId, $activeCatalogVariants)) {
                    $validator->errors()->add(
                        'items',
                        "Product variant ID {$variantId} is not in the vendor's active catalog.",
                    );
                }
            }
        });
    }
}
