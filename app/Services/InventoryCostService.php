<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\ReceptionOrderProduct;
use App\Models\SalesOrder;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class InventoryCostService
{
    public function __construct(private readonly AmountAllocationService $allocator) {}

    public function receiptUnitCost(ReceptionOrderProduct $line, PurchaseOrder $purchaseOrder): ?string
    {
        $purchaseOrder->loadMissing('lineItems');
        $weights = [];
        foreach ($purchaseOrder->lineItems as $item) {
            $weights[$item->id] = (string) $item->total;
        }
        $purchaseLine = $purchaseOrder->lineItems->firstWhere('id', $line->purchase_order_item_id);
        if ($purchaseLine === null || (float) $purchaseLine->quantity <= 0 || $line->conversion_factor <= 0) {
            return null;
        }

        $net = BigDecimal::of((string) $purchaseOrder->sub_total)->minus((string) $purchaseOrder->discount);
        $amounts = $this->allocator->allocate((string) $net, $weights);

        return (string) BigDecimal::of($amounts[$purchaseLine->id])
            ->dividedBy(BigDecimal::of((string) $purchaseLine->quantity)->multipliedBy($line->conversion_factor), 12, RoundingMode::HalfUp);
    }

    /** Must run inside the fulfillment transaction, before the order is recognized. */
    public function snapshotLineRevenue(SalesOrder $order): void
    {
        $weights = [];
        foreach ($order->items as $item) {
            $weights[$item->id] = (string) $item->line_total;
        }
        $net = BigDecimal::of((string) $order->total)->minus((string) $order->tax_amount);
        $amounts = $this->allocator->allocate((string) $net, $weights);
        foreach ($order->items as $item) {
            $item->update(['net_sales_amount' => $amounts[$item->id]]);
        }
    }
}
