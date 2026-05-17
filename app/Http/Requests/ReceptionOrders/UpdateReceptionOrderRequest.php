<?php

declare(strict_types=1);

namespace App\Http\Requests\ReceptionOrders;

use App\Enums\PermissionsEnum;
use App\Models\Catalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateReceptionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::RECEPTION_ORDERS_EDIT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['sometimes', 'required', 'integer', 'exists:stores,id'],
            'reception_date' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'items' => ['sometimes', 'required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required_with:items', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
            'items.*.expiry_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var \App\Models\ReceptionOrder|null $receptionOrder */
            $receptionOrder = $this->route('receptionOrder');

            if ($receptionOrder === null) {
                return;
            }

            if ($receptionOrder->status !== 'pending') {
                $validator->errors()->add(
                    'status',
                    'Only pending reception orders can be updated.',
                );

                return;
            }

            $items = $this->input('items');

            if ($items === null) {
                return;
            }

            $purchaseOrder = $receptionOrder->purchaseOrder;

            if ($purchaseOrder === null) {
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
