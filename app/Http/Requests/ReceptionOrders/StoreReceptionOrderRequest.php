<?php

declare(strict_types=1);

namespace App\Http\Requests\ReceptionOrders;

use App\Enums\PermissionsEnum;
use App\Models\Catalog;
use App\Models\PurchaseOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreReceptionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::RECEPTION_ORDERS_CREATE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'purchase_order_id' => ['required', 'integer', 'exists:purchase_orders,id'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'reception_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.expiry_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $purchaseOrderId = $this->integer('purchase_order_id');
            $items = $this->array('items');

            if ($purchaseOrderId === 0 || $items === []) {
                return;
            }

            $purchaseOrder = PurchaseOrder::find($purchaseOrderId);

            if ($purchaseOrder === null) {
                return;
            }

            $blockedStatuses = ['draft', 'awaiting_approval', 'paid', 'cancelled'];

            if (in_array($purchaseOrder->status, $blockedStatuses, true)) {
                $validator->errors()->add(
                    'purchase_order_id',
                    "Cannot create a reception order against a purchase order with status: {$purchaseOrder->status}.",
                );

                return;
            }

            $poVariantIds = $purchaseOrder->lineItems->pluck('product_variant_id')->toArray();
            $vendorId = $purchaseOrder->vendor_id;
            $variantIds = array_map(fn (array $item) => $item['product_variant_id'], $items);

            foreach ($variantIds as $variantId) {
                if (! in_array($variantId, $poVariantIds, true)) {
                    $validator->errors()->add(
                        'items',
                        "Product variant ID {$variantId} is not in purchase order #{$purchaseOrder->id}'s line items.",
                    );
                }
            }

            $activeCatalogVariants = Catalog::query()
                ->where('vendor_id', $vendorId)
                ->where('status', 'active')
                ->whereIn('product_variant_id', $variantIds)
                ->pluck('product_variant_id')
                ->toArray();

            foreach ($variantIds as $variantId) {
                if (! in_array($variantId, $activeCatalogVariants, true)) {
                    $validator->errors()->add(
                        'items',
                        "Product variant ID {$variantId} is not in the vendor's active catalog.",
                    );
                }
            }
        });
    }
}
