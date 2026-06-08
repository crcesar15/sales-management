<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use InvalidArgumentException;

final class FifoStockDeductionService
{
    /**
     * Deduct stock for all items in the order using FIFO (oldest batch first).
     * Must be called within an existing DB::transaction.
     *
     * @throws InvalidArgumentException if insufficient stock is available
     */
    public function deductForOrder(SalesOrder $order): void
    {
        $affectedVariantIds = [];

        foreach ($order->items as $item) {
            $baseQuantity = $item->quantity * $item->conversion_factor;

            $batches = Batch::where('product_variant_id', $item->product_variant_id)
                ->where('store_id', $order->store_id)
                ->where('status', 'active')
                ->where('remaining_quantity', '>', 0)
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            $totalAvailable = $batches->sum('remaining_quantity');

            if ($totalAvailable < $baseQuantity) {
                $variant = ProductVariant::find($item->product_variant_id);
                $sku = $variant !== null ? $variant->identifier : "ID {$item->product_variant_id}";

                throw new InvalidArgumentException(
                    "Insufficient stock for variant {$sku}: requested {$baseQuantity}, available {$totalAvailable}."
                );
            }

            $remaining = $baseQuantity;

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $deduct = min($remaining, (int) $batch->remaining_quantity);
                $batch->decrement('remaining_quantity', $deduct);
                $batch->increment('sold_quantity', $deduct);
                $remaining -= $deduct;
            }

            $affectedVariantIds[] = $item->product_variant_id;
        }

        // Recalculate stock for all affected variants
        $uniqueVariantIds = array_unique($affectedVariantIds);

        foreach ($uniqueVariantIds as $variantId) {
            $variant = ProductVariant::find($variantId);

            if ($variant) {
                $variant->recalculateStock();
            }
        }
    }
}
