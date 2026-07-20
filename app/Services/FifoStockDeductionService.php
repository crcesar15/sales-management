<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderStockAllocation;
use Illuminate\Support\Facades\DB;
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

            $batches = Batch::query()->where('product_variant_id', $item->product_variant_id)
                ->where('store_id', $order->store_id)
                ->where('status', 'active')
                ->where('remaining_quantity', '>', 0)
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            $totalAvailable = $batches->sum('remaining_quantity');

            if ($totalAvailable < $baseQuantity) {
                /** @var ProductVariant|null $variant */
                $variant = ProductVariant::find($item->product_variant_id);
                $sku = $variant === null ? "ID {$item->product_variant_id}" : $variant->identifier;

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
                SalesOrderStockAllocation::create([
                    'sales_order_item_id' => $item->id,
                    'batch_id' => $batch->id,
                    'quantity' => $deduct,
                ]);
                $remaining -= $deduct;

                $batch->refresh();
                if ($batch->remaining_quantity === 0) {
                    $batch->update(['status' => 'closed']);
                }

                $affectedVariantIds[$item->product_variant_id] = true;
            }
        }

        // Recalculate stock for all affected variants
        $uniqueVariantIds = array_keys($affectedVariantIds);

        foreach ($uniqueVariantIds as $variantId) {
            $variant = ProductVariant::find($variantId);

            if ($variant === null) {
                throw new InvalidArgumentException("Product variant ID {$variantId} not found.");
            }

            $variant->recalculateStock();
        }
    }

    /**
     * Deduct stock for a transfer using FIFO.
     * Opens its own DB::transaction.
     *
     * @throws InvalidArgumentException if insufficient stock is available
     */
    public function deductForTransfer(int $variantId, int $storeId, int $quantity): void
    {
        DB::transaction(function () use ($variantId, $storeId, $quantity): void {
            $remaining = $quantity;

            $batches = Batch::query()
                ->where('product_variant_id', $variantId)
                ->where('store_id', $storeId)
                ->where('status', 'active')
                ->where('remaining_quantity', '>', 0)
                ->orderBy('created_at', 'asc')
                ->lockForUpdate()
                ->get();

            $totalAvailable = $batches->sum('remaining_quantity');

            if ($totalAvailable < $quantity) {
                /** @var ProductVariant|null $variant */
                $variant = ProductVariant::find($variantId);
                $sku = $variant === null ? "ID {$variantId}" : $variant->identifier;

                throw new InvalidArgumentException(
                    "Insufficient stock for variant {$sku}: requested {$quantity}, available {$totalAvailable}."
                );
            }

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $deduct = min($remaining, (int) $batch->remaining_quantity);
                $batch->decrement('remaining_quantity', $deduct);
                $batch->increment('transferred_quantity', $deduct);
                $remaining -= $deduct;

                $batch->refresh();
                if ($batch->remaining_quantity === 0) {
                    $batch->update(['status' => 'closed']);
                }
            }

            $variant = ProductVariant::find($variantId);

            if ($variant === null) {
                throw new InvalidArgumentException("Product variant ID {$variantId} not found.");
            }

            $variant->recalculateStock();
        });
    }
}
