<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\PurchaseOrders;

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
            'items.*.catalog_id' => ['required_with:items', 'integer', 'exists:catalog,id'],
            'items.*.unit_id' => ['nullable', 'integer', 'exists:product_variant_units,id'],
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
            $catalogIds = array_map(fn (array $item) => $item['catalog_id'], $items);

            $activeCatalogIds = Catalog::query()
                ->where('vendor_id', $vendorId)
                ->where('status', 'active')
                ->whereIn('id', $catalogIds)
                ->pluck('id')
                ->toArray();

            foreach ($catalogIds as $catalogId) {
                if (! in_array($catalogId, $activeCatalogIds)) {
                    $validator->errors()->add(
                        'items',
                        "Catalog entry ID {$catalogId} is not in the vendor's active catalog.",
                    );
                }
            }
        });
    }
}