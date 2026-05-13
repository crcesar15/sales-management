<?php

declare(strict_types=1);

namespace App\Http\Requests\PurchaseOrders;

use App\Enums\PermissionsEnum;
use App\Models\Catalog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionsEnum::PURCHASE_ORDERS_CREATE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],
            'order_date' => ['required', 'date'],
            'expected_arrival_date' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof_of_payment_type' => ['nullable', 'string', 'max:50'],
            'proof_of_payment_number' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $vendorId = $this->integer('vendor_id');
            $items = $this->array('items');

            if ($vendorId === 0 || $items === []) {
                return;
            }

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

            $discount = $this->input('discount');
            if ($discount !== null && $discount > 0) {
                $catalogPrices = Catalog::query()
                    ->where('vendor_id', $vendorId)
                    ->where('status', 'active')
                    ->whereIn('product_variant_id', $variantIds)
                    ->pluck('price', 'product_variant_id');

                $subTotal = 0;
                foreach ($items as $item) {
                    $price = $catalogPrices->get($item['product_variant_id'], 0);
                    $subTotal += (float) $price * (float) $item['quantity'];
                }

                if ((float) $discount > $subTotal) {
                    $validator->errors()->add('discount', 'Discount cannot exceed the sub total.');
                }
            }
        });
    }
}
