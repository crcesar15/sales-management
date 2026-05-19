<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderProduct;
use App\Models\ReceptionOrder;
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
            ->with(['purchaseOrder', 'vendor', 'store', 'user', 'lineItems.productVariant.product'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['purchase_order_id'] ?? null, fn ($q, $poId) => $q->where('purchase_order_id', $poId))
            ->when($filters['vendor_id'] ?? null, fn ($q, $vendorId) => $q->where('vendor_id', $vendorId))
            ->when($filters['store_id'] ?? null, fn ($q, $storeId) => $q->where('store_id', $storeId))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('reception_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('reception_date', '<=', $to))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): ReceptionOrder
    {
        return DB::transaction(function () use ($data, $actor): ReceptionOrder {
            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = PurchaseOrder::findOrFail($data['purchase_order_id']);

            $this->guardPurchaseOrderStatus($purchaseOrder);

            $items = $data['items'];
            $this->guardItemsBelongToPo($items, $purchaseOrder);
            $this->guardAgainstOverReceiving($items, $purchaseOrder);

            $poLineItems = $purchaseOrder->lineItems->keyBy('id');

            $receptionOrder = ReceptionOrder::create([
                'purchase_order_id' => $purchaseOrder->id,
                'user_id' => $actor->id,
                'vendor_id' => $purchaseOrder->vendor_id,
                'store_id' => $data['store_id'],
                'reception_date' => $data['reception_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);

            foreach ($items as $item) {
                $poLineItem = $poLineItems->get($item['purchase_order_item_id']);
                $price = (float) ($poLineItem->price ?? 0);
                $quantity = (float) $item['quantity'];
                $lineTotal = $price * $quantity;

                ReceptionOrderProduct::create([
                    'reception_order_id' => $receptionOrder->id,
                    'purchase_order_item_id' => $item['purchase_order_item_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $lineTotal,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'batch_identifier' => $item['batch_identifier'] ?? null,
                ]);
            }

            activity()
                ->causedBy($actor)
                ->performedOn($receptionOrder)
                ->log('created');

            return $receptionOrder->load([
                'purchaseOrder', 'vendor', 'store', 'user',
                'lineItems.productVariant.product',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ReceptionOrder $receptionOrder, array $data, User $actor): ReceptionOrder
    {
        if ($receptionOrder->status !== 'pending') {
            throw new InvalidArgumentException('Only pending reception orders can be updated.');
        }

        return DB::transaction(function () use ($receptionOrder, $data, $actor): ReceptionOrder {
            $items = $data['items'] ?? null;
            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = $receptionOrder->purchaseOrder;

            if ($items !== null) {
                $this->guardItemsBelongToPo($items, $purchaseOrder);
                $this->guardAgainstOverReceiving($items, $purchaseOrder, $receptionOrder->id);

                $poLineItems = $purchaseOrder->lineItems->keyBy('id');

                $receptionOrder->lineItems()->delete();

                foreach ($items as $item) {
                    $poLineItem = $poLineItems->get($item['purchase_order_item_id']);
                    $price = (float) ($poLineItem->price ?? 0);
                    $quantity = (float) $item['quantity'];
                    $lineTotal = $price * $quantity;

                    ReceptionOrderProduct::create([
                        'reception_order_id' => $receptionOrder->id,
                        'purchase_order_item_id' => $item['purchase_order_item_id'],
                        'product_variant_id' => $item['product_variant_id'],
                        'quantity' => $quantity,
                        'price' => $price,
                        'total' => $lineTotal,
                        'expiry_date' => $item['expiry_date'] ?? null,
                        'batch_identifier' => $item['batch_identifier'] ?? null,
                    ]);
                }
            }

            $updateData = [
                'store_id' => $data['store_id'] ?? $receptionOrder->store_id,
                'reception_date' => $data['reception_date'] ?? $receptionOrder->reception_date,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $receptionOrder->notes,
            ];

            $receptionOrder->update($updateData);

            activity()
                ->causedBy($actor)
                ->performedOn($receptionOrder)
                ->log('updated');

            return $receptionOrder->load([
                'purchaseOrder', 'vendor', 'store', 'user',
                'lineItems.productVariant.product',
            ]);
        });
    }

    public function complete(ReceptionOrder $receptionOrder, User $actor): ReceptionOrder
    {
        if (in_array($receptionOrder->status, ['completed', 'cancelled'], true)) {
            throw new InvalidArgumentException("Cannot complete a reception order with status: {$receptionOrder->status}.");
        }

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $receptionOrder->purchaseOrder;
        $this->guardPurchaseOrderStatus($purchaseOrder);

        return DB::transaction(function () use ($receptionOrder, $purchaseOrder, $actor): ReceptionOrder {
            $receptionOrder->load('lineItems');

            $poLineItemIds = $receptionOrder->lineItems->pluck('purchase_order_item_id')->filter()->unique()->toArray();
            $poLineItems = PurchaseOrderProduct::with('catalog.unit')
                ->whereIn('id', $poLineItemIds)
                ->get()
                ->keyBy('id');

            $stockChanges = [];

            foreach ($receptionOrder->lineItems as $lineItem) {
                $poLineItem = $poLineItems->get($lineItem->purchase_order_item_id);
                $catalogEntry = $poLineItem?->catalog;
                $conversionFactor = $catalogEntry?->unit->conversion_factor ?? 1;

                $baseQuantity = (int) round($lineItem->quantity * $conversionFactor);

                ProductVariant::where('id', $lineItem->product_variant_id)
                    ->increment('stock', $baseQuantity);

                Batch::create([
                    'product_variant_id' => $lineItem->product_variant_id,
                    'reception_order_id' => $receptionOrder->id,
                    'store_id' => $receptionOrder->store_id,
                    'expiry_date' => $lineItem->expiry_date,
                    'batch_identifier' => $lineItem->batch_identifier,
                    'initial_quantity' => $baseQuantity,
                    'remaining_quantity' => $baseQuantity,
                    'missing_quantity' => 0,
                    'sold_quantity' => 0,
                    'transferred_quantity' => 0,
                    'status' => 'queued',
                ]);

                $stockChanges[$lineItem->product_variant_id] = $baseQuantity;
            }

            $receptionOrder->update(['status' => 'completed']);

            $purchaseOrderService = new PurchaseOrderService;
            $purchaseOrderService->updateReceptionStatus($purchaseOrder);

            activity()
                ->causedBy($actor)
                ->performedOn($receptionOrder)
                ->withProperties(['stock_changes' => $stockChanges])
                ->log('completed');

            return $receptionOrder->load([
                'purchaseOrder', 'vendor', 'store', 'user',
                'lineItems.productVariant.product',
            ]);
        });
    }

    public function cancel(ReceptionOrder $receptionOrder, ?string $reason, User $actor): void
    {
        if (in_array($receptionOrder->status, ['completed', 'cancelled'], true)) {
            throw new InvalidArgumentException("Cannot cancel a reception order with status: {$receptionOrder->status}.");
        }

        DB::transaction(function () use ($receptionOrder, $reason, $actor): void {
            $receptionOrder->update(['status' => 'cancelled']);

            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = $receptionOrder->purchaseOrder;
            $purchaseOrderService = new PurchaseOrderService;
            $purchaseOrderService->updateReceptionStatus($purchaseOrder);

            activity()
                ->causedBy($actor)
                ->performedOn($receptionOrder)
                ->withProperties(['reason' => $reason])
                ->log('cancelled');
        });
    }

    private function guardPurchaseOrderStatus(PurchaseOrder $po): void
    {
        $blocked = ['draft', 'awaiting_approval', 'cancelled', 'received'];

        if (in_array($po->status, $blocked, true)) {
            throw new InvalidArgumentException("Cannot create a reception order against a purchase order with status: {$po->status}.");
        }
    }

    /**
     * @param  array<int, array{purchase_order_item_id: int}>  $items
     */
    private function guardItemsBelongToPo(array $items, PurchaseOrder $po): void
    {
        $poItemIds = $po->lineItems->pluck('id')->toArray();

        foreach ($items as $item) {
            if (! in_array($item['purchase_order_item_id'], $poItemIds, true)) {
                throw new InvalidArgumentException(
                    "Purchase order item ID {$item['purchase_order_item_id']} is not in purchase order #{$po->id}'s line items."
                );
            }
        }
    }

    /**
     * Validate that proposed reception quantities do not exceed remaining ordered quantities.
     *
     * @param  array<int, array{purchase_order_item_id: int, quantity: float|int|string}>  $items
     * @param  int|null  $excludeReceptionOrderId  Exclude this RO's quantities (for updates)
     */
    private function guardAgainstOverReceiving(array $items, PurchaseOrder $po, ?int $excludeReceptionOrderId = null): void
    {
        $poLineItems = $po->lineItems->keyBy('id');

        $claimedQuantities = $this->getClaimedQuantities($po, $excludeReceptionOrderId);

        foreach ($items as $item) {
            $poItemId = $item['purchase_order_item_id'];
            $poLineItem = $poLineItems->get($poItemId);

            if ($poLineItem === null) {
                continue;
            }

            $orderedQuantity = (float) $poLineItem->quantity;
            $alreadyClaimed = (float) ($claimedQuantities[$poItemId] ?? 0);
            $proposedQuantity = (float) $item['quantity'];
            $remaining = $orderedQuantity - $alreadyClaimed;

            if ($proposedQuantity > $remaining) {
                throw new InvalidArgumentException(
                    "Cannot receive {$proposedQuantity} units of this product variant. Only {$remaining} remaining of {$orderedQuantity} ordered ({$alreadyClaimed} already claimed by other reception orders)."
                );
            }
        }
    }

    /**
     * Get the total claimed quantities per PO line item for a PO, from all non-cancelled reception orders.
     *
     * @return array<int, string> keyed by purchase_order_item_id
     */
    private function getClaimedQuantities(PurchaseOrder $po, ?int $excludeReceptionOrderId = null): array
    {
        $receptionOrders = $po->receptionOrders()
            ->where('status', '!=', 'cancelled')
            ->when($excludeReceptionOrderId, fn ($q) => $q->where('id', '!=', $excludeReceptionOrderId))
            ->with('lineItems')
            ->get();

        $quantities = [];

        foreach ($receptionOrders as $receptionOrder) {
            foreach ($receptionOrder->lineItems as $lineItem) {
                $poItemId = (int) $lineItem->purchase_order_item_id;
                $quantities[$poItemId] = bcadd((string) ($quantities[$poItemId] ?? '0'), (string) $lineItem->quantity, 4);
            }
        }

        return $quantities;
    }
}
