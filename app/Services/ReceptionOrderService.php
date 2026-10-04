<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\ReceptionOrder;
use App\Models\ReceptionOrderBatchAllocation;
use App\Models\ReceptionOrderProduct;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ReceptionOrderService
{
    /**
     * @param  array{status?: string|null, purchase_order_id?: int|null, vendor_id?: int|null, store_id?: int|null, from?: string|null, to?: string|null}  $filters
     * @return LengthAwarePaginator<int, ReceptionOrder>
     */
    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return ReceptionOrder::query()
            ->with(['purchaseOrder', 'vendor', 'store', 'user', 'lineItems.productVariant.product.brand', 'lineItems.productVariant.product.measurementUnit', 'lineItems.purchaseOrderItem.unit', 'lineItems.batchAllocations'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['purchase_order_id'] ?? null, fn ($q, $poId) => $q->where('purchase_order_id', $poId))
            ->when($filters['vendor_id'] ?? null, fn ($q, $vendorId) => $q->where('vendor_id', $vendorId))
            ->when($filters['store_id'] ?? null, fn ($q, $storeId) => $q->where('store_id', $storeId))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('reception_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('reception_date', '<=', $to))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): ReceptionOrder
    {
        return DB::transaction(function () use ($data, $actor): ReceptionOrder {
            $purchaseOrder = PurchaseOrder::query()->with('lineItems.unit')->lockForUpdate()->whereKey($data['purchase_order_id'])->sole();
            $this->guardPurchaseOrderStatus($purchaseOrder);
            $items = $this->normalizeItems($data['items'], $purchaseOrder);
            $this->guardAgainstOverReceiving($items, $purchaseOrder);

            $receptionOrder = ReceptionOrder::create([
                'purchase_order_id' => $purchaseOrder->id,
                'user_id' => $actor->id,
                'vendor_id' => $purchaseOrder->vendor_id,
                'store_id' => $data['store_id'],
                'reception_date' => $data['reception_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);

            $this->storeItems($receptionOrder, $items, $purchaseOrder);

            activity()->causedBy($actor)->performedOn($receptionOrder)->log('created');

            return $this->loadReceptionOrder($receptionOrder);
        }, attempts: 3);
    }

    /** @param array<string, mixed> $data */
    public function update(ReceptionOrder $receptionOrder, array $data, User $actor): ReceptionOrder
    {
        return DB::transaction(function () use ($receptionOrder, $data, $actor): ReceptionOrder {
            $receptionOrder = ReceptionOrder::query()->lockForUpdate()->whereKey($receptionOrder->id)->sole();

            if ($receptionOrder->status !== 'pending') {
                throw new InvalidArgumentException('Only pending reception orders can be updated.');
            }

            $purchaseOrder = PurchaseOrder::query()->with('lineItems.unit')->lockForUpdate()->whereKey($receptionOrder->purchase_order_id)->sole();

            if (array_key_exists('items', $data)) {
                $items = $this->normalizeItems($data['items'], $purchaseOrder);
                $this->guardAgainstOverReceiving($items, $purchaseOrder, $receptionOrder->id);
                $receptionOrder->lineItems()->delete();
                $this->storeItems($receptionOrder, $items, $purchaseOrder);
            }

            $receptionOrder->update([
                'store_id' => $data['store_id'] ?? $receptionOrder->store_id,
                'reception_date' => $data['reception_date'] ?? $receptionOrder->reception_date,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $receptionOrder->notes,
            ]);

            activity()->causedBy($actor)->performedOn($receptionOrder)->log('updated');

            return $this->loadReceptionOrder($receptionOrder);
        }, attempts: 3);
    }

    public function complete(ReceptionOrder $receptionOrder, User $actor): ReceptionOrder
    {
        return DB::transaction(function () use ($receptionOrder, $actor): ReceptionOrder {
            $receptionOrder = ReceptionOrder::query()
                ->with('lineItems.batchAllocations')
                ->lockForUpdate()
                ->whereKey($receptionOrder->id)
                ->sole();

            if ($receptionOrder->status !== 'pending') {
                throw new InvalidArgumentException("Cannot complete a reception order with status: {$receptionOrder->status}.");
            }

            $purchaseOrder = PurchaseOrder::query()->lockForUpdate()->whereKey($receptionOrder->purchase_order_id)->sole();
            $this->guardPurchaseOrderStatus($purchaseOrder);
            $stockChanges = [];

            foreach ($receptionOrder->lineItems as $lineItem) {
                foreach ($lineItem->batchAllocations as $allocation) {
                    Batch::create([
                        'product_variant_id' => $lineItem->product_variant_id,
                        'reception_order_id' => $receptionOrder->id,
                        'reception_order_batch_allocation_id' => $allocation->id,
                        'store_id' => $receptionOrder->store_id,
                        'expiry_date' => $allocation->expiry_date,
                        'batch_identifier' => $allocation->batch_identifier,
                        'initial_quantity' => $allocation->base_quantity,
                        'remaining_quantity' => $allocation->base_quantity,
                        'missing_quantity' => 0,
                        'sold_quantity' => 0,
                        'transferred_quantity' => 0,
                        'status' => 'active',
                    ]);
                    $stockChanges[$lineItem->product_variant_id] = ($stockChanges[$lineItem->product_variant_id] ?? 0) + $allocation->base_quantity;
                }
            }

            foreach (array_keys($stockChanges) as $variantId) {
                ProductVariant::query()->whereKey($variantId)->sole()->recalculateStock();
            }

            $receptionOrder->update(['status' => 'completed']);
            (new PurchaseOrderService)->updateReceptionStatus($purchaseOrder);

            activity()->causedBy($actor)->performedOn($receptionOrder)->withProperties(['stock_changes' => $stockChanges])->log('completed');

            return $this->loadReceptionOrder($receptionOrder);
        }, attempts: 3);
    }

    public function cancel(ReceptionOrder $receptionOrder, ?string $reason, User $actor): void
    {
        DB::transaction(function () use ($receptionOrder, $reason, $actor): void {
            $receptionOrder = ReceptionOrder::query()->lockForUpdate()->whereKey($receptionOrder->id)->sole();
            if ($receptionOrder->status !== 'pending') {
                throw new InvalidArgumentException("Cannot cancel a reception order with status: {$receptionOrder->status}.");
            }
            $receptionOrder->update(['status' => 'cancelled']);
            $purchaseOrder = PurchaseOrder::query()->whereKey($receptionOrder->purchase_order_id)->sole();
            (new PurchaseOrderService)->updateReceptionStatus($purchaseOrder);
            activity()->causedBy($actor)->performedOn($receptionOrder)->withProperties(['reason' => $reason])->log('cancelled');
        }, attempts: 3);
    }

    /** @return array<int, string> */
    public function getClaimedQuantities(PurchaseOrder $po, ?int $excludeReceptionOrderId = null): array
    {
        return $po->receptionOrders()
            ->where('status', '!=', 'cancelled')
            ->when($excludeReceptionOrderId, fn ($query) => $query->where('id', '!=', $excludeReceptionOrderId))
            ->with('lineItems')
            ->get()
            ->flatMap(fn (ReceptionOrder $receptionOrder) => $receptionOrder->lineItems)
            ->groupBy('purchase_order_item_id')
            ->map(fn ($items) => number_format((float) $items->sum('quantity'), 4, '.', ''))
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(array $items, PurchaseOrder $purchaseOrder): array
    {
        $poLineItems = $purchaseOrder->lineItems->keyBy('id');
        $normalized = [];

        foreach ($items as $index => $item) {
            $poLineItem = $poLineItems->get($item['purchase_order_item_id'] ?? null);
            if ($poLineItem === null) {
                throw new InvalidArgumentException("Purchase order item at position {$index} does not belong to purchase order #{$purchaseOrder->id}.");
            }
            $unit = $poLineItem->unit;
            $conversionFactor = $unit === null ? 1 : $unit->conversion_factor;
            $baseQuantity = 0;
            foreach ($item['batches'] ?? [] as $batch) {
                $purchaseUnits = (int) ($batch['purchase_unit_quantity'] ?? 0);
                $looseUnits = (int) ($batch['loose_unit_quantity'] ?? 0);
                if ($purchaseUnits < 0 || $looseUnits < 0 || $looseUnits >= $conversionFactor) {
                    throw new InvalidArgumentException('Each batch must contain valid purchase and loose unit quantities.');
                }
                $baseQuantity += ($purchaseUnits * $conversionFactor) + $looseUnits;
            }
            if ($baseQuantity === 0 || $baseQuantity % $conversionFactor !== 0) {
                throw new InvalidArgumentException('Batch allocations must add up to a whole received purchase unit quantity.');
            }
            $normalized[] = [
                'purchase_order_item_id' => $poLineItem->id,
                'product_variant_id' => $poLineItem->product_variant_id,
                'quantity' => $baseQuantity / $conversionFactor,
                'conversion_factor' => $conversionFactor,
                'batches' => $item['batches'],
            ];
        }

        return $normalized;
    }

    /** @param array<int, array<string, mixed>> $items */
    private function storeItems(ReceptionOrder $receptionOrder, array $items, PurchaseOrder $purchaseOrder): void
    {
        $poLineItems = $purchaseOrder->lineItems->keyBy('id');
        foreach ($items as $item) {
            $poLineItem = $poLineItems->get($item['purchase_order_item_id']);
            if ($poLineItem === null) {
                throw new InvalidArgumentException('The selected purchase order item could not be found.');
            }
            $lineItem = ReceptionOrderProduct::create([
                'reception_order_id' => $receptionOrder->id,
                'purchase_order_item_id' => $item['purchase_order_item_id'],
                'product_variant_id' => $item['product_variant_id'],
                'quantity' => $item['quantity'],
                'conversion_factor' => $item['conversion_factor'],
                'price' => $poLineItem->price,
                'total' => round((float) $poLineItem->price * $item['quantity'], 2),
            ]);
            foreach ($item['batches'] as $batch) {
                $purchaseUnits = (int) ($batch['purchase_unit_quantity'] ?? 0);
                $looseUnits = (int) ($batch['loose_unit_quantity'] ?? 0);
                ReceptionOrderBatchAllocation::create([
                    'reception_order_product_id' => $lineItem->id,
                    'purchase_unit_quantity' => $purchaseUnits,
                    'loose_unit_quantity' => $looseUnits,
                    'base_quantity' => ($purchaseUnits * $item['conversion_factor']) + $looseUnits,
                    'expiry_date' => $batch['expiry_date'] ?? null,
                    'batch_identifier' => $batch['batch_identifier'] ?? null,
                ]);
            }
        }
    }

    /** @param array<int, array<string, mixed>> $items */
    private function guardAgainstOverReceiving(array $items, PurchaseOrder $po, ?int $excludeReceptionOrderId = null): void
    {
        $claimed = $this->getClaimedQuantities($po, $excludeReceptionOrderId);
        foreach ($items as $item) {
            $poLineItem = $po->lineItems->firstWhere('id', $item['purchase_order_item_id']);
            if ($poLineItem === null) {
                throw new InvalidArgumentException('The selected purchase order item could not be found.');
            }
            $ordered = (float) $poLineItem->quantity;
            $available = $ordered - (float) ($claimed[$item['purchase_order_item_id']] ?? 0);
            if ($item['quantity'] > $available) {
                throw new InvalidArgumentException("Cannot receive {$item['quantity']} units. Only {$available} are available to receive.");
            }
        }
    }

    private function guardPurchaseOrderStatus(PurchaseOrder $purchaseOrder): void
    {
        if (! in_array($purchaseOrder->status, ['sent', 'partially_received'], true)) {
            throw new InvalidArgumentException("Cannot create a reception order against a purchase order with status: {$purchaseOrder->status}.");
        }
    }

    private function loadReceptionOrder(ReceptionOrder $receptionOrder): ReceptionOrder
    {
        return $receptionOrder->load(['purchaseOrder', 'vendor', 'store', 'user', 'lineItems.productVariant.product.brand', 'lineItems.productVariant.product.measurementUnit', 'lineItems.purchaseOrderItem.unit', 'lineItems.batchAllocations']);
    }
}
