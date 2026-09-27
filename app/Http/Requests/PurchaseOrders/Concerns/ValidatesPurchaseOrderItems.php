<?php

declare(strict_types=1);

namespace App\Http\Requests\PurchaseOrders\Concerns;

use App\Models\Catalog;
use Illuminate\Validation\Validator;

trait ValidatesPurchaseOrderItems
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function validatePurchaseOrderItems(Validator $validator, int $vendorId, array $items): void
    {
        if ($vendorId === 0 || $items === []) {
            return;
        }

        $catalogEntries = Catalog::query()
            ->where('vendor_id', $vendorId)
            ->where('status', 'active')
            ->whereIn('id', array_column($items, 'catalog_id'))
            ->get(['id', 'product_variant_id', 'unit_id', 'minimum_order_quantity'])
            ->keyBy('id');

        foreach ($items as $index => $item) {
            $catalogId = (int) ($item['catalog_id'] ?? 0);
            $catalogEntry = $catalogEntries->get($catalogId);

            if ($catalogEntry === null) {
                $validator->errors()->add("items.{$index}.catalog_id", 'The selected catalog entry is not active for this vendor.');

                continue;
            }

            if ((int) ($item['product_variant_id'] ?? 0) !== $catalogEntry->product_variant_id) {
                $validator->errors()->add("items.{$index}.product_variant_id", 'The selected product does not match the catalog entry.');
            }

            $unitId = isset($item['unit_id']) ? (int) $item['unit_id'] : null;
            if ($unitId !== $catalogEntry->unit_id) {
                $validator->errors()->add("items.{$index}.unit_id", 'The selected purchase unit does not match the catalog entry.');
            }

            $minimumOrderQuantity = max(1, (int) ceil((float) ($catalogEntry->minimum_order_quantity ?? 1)));
            if ((int) ($item['quantity'] ?? 0) < $minimumOrderQuantity) {
                $validator->errors()->add(
                    "items.{$index}.quantity",
                    "The quantity must be at least {$minimumOrderQuantity} for the selected catalog entry.",
                );
            }
        }
    }
}
