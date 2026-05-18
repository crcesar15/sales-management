<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\Catalog;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\ReceptionOrder;
use App\Models\ReceptionOrderProduct;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

            $resolvedPrices = $this->resolvePrices($purchaseOrder->vendor_id, $items);

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
                $price = $resolvedPrices->get($item['product_variant_id']);
                $quantity = (float) $item['quantity'];
                $lineTotal = $price * $quantity;

                ReceptionOrderProduct::create([
                    'reception_order_id' => $receptionOrder->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $lineTotal,
                    'expiry_date' => $item['expiry_date'] ?? null,
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
                $resolvedPrices = $this->resolvePrices($purchaseOrder->vendor_id, $items);

                $receptionOrder->lineItems()->delete();

                foreach ($items as $item) {
                    $price = $resolvedPrices->get($item['product_variant_id']);
                    $quantity = (float) $item['quantity'];
                    $lineTotal = $price * $quantity;

                    ReceptionOrderProduct::create([
                        'reception_order_id' => $receptionOrder->id,
                        'product_variant_id' => $item['product_variant_id'],
                        'quantity' => $quantity,
                        'price' => $price,
                        'total' => $lineTotal,
                        'expiry_date' => $item['expiry_date'] ?? null,
                    ]);
                }
            }

            $updateData = [
                'store_id' => $data['store_id'] ?? $receptionOrder->store_id,
                'reception_date' => $data['reception_date'] ?? $receptionOrder->reception_date,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $receptionOrder->notes,
            ];

            if ($items !== null) {
                $updateData['status'] = 'uncompleted';
            }

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

        return DB::transaction(function () use ($receptionOrder, $actor): ReceptionOrder {
            $receptionOrder->load('lineItems');

            $catalogEntries = Catalog::query()
                ->where('vendor_id', $receptionOrder->vendor_id)
                ->where('status', 'active')
                ->whereIn('product_variant_id', $receptionOrder->lineItems->pluck('product_variant_id'))
                ->with('unit')
                ->get()
                ->keyBy('product_variant_id');

            $stockChanges = [];

            foreach ($receptionOrder->lineItems as $lineItem) {
                $catalogEntry = $catalogEntries->get($lineItem->product_variant_id);
                $conversionFactor = $catalogEntry?->unit->conversion_factor ?? 1;

                $baseQuantity = (int) round($lineItem->quantity * $conversionFactor);

                ProductVariant::where('id', $lineItem->product_variant_id)
                    ->increment('stock', $baseQuantity);

                Batch::create([
                    'product_variant_id' => $lineItem->product_variant_id,
                    'reception_order_id' => $receptionOrder->id,
                    'store_id' => $receptionOrder->store_id,
                    'expiry_date' => $lineItem->expiry_date,
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

            activity()
                ->causedBy($actor)
                ->performedOn($receptionOrder)
                ->withProperties(['reason' => $reason])
                ->log('cancelled');
        });
    }

    private function guardPurchaseOrderStatus(PurchaseOrder $po): void
    {
        $blocked = ['draft', 'awaiting_approval', 'paid', 'cancelled'];

        if (in_array($po->status, $blocked, true)) {
            throw new InvalidArgumentException("Cannot create a reception order against a purchase order with status: {$po->status}.");
        }
    }

    /**
     * @param  array<int, array{product_variant_id: int}>  $items
     */
    private function guardItemsBelongToPo(array $items, PurchaseOrder $po): void
    {
        $poVariantIds = $po->lineItems->pluck('product_variant_id')->toArray();

        foreach ($items as $item) {
            if (! in_array($item['product_variant_id'], $poVariantIds, true)) {
                throw new InvalidArgumentException(
                    "Product variant ID {$item['product_variant_id']} is not in purchase order #{$po->id}'s line items."
                );
            }
        }
    }

    /**
     * @param  array<int, array{product_variant_id: int}>  $items
     * @return Collection<int, float> keyed by product_variant_id
     */
    private function resolvePrices(int $vendorId, array $items): Collection
    {
        $variantIds = array_map(fn (array $item) => $item['product_variant_id'], $items);

        $catalogEntries = Catalog::query()
            ->where('vendor_id', $vendorId)
            ->where('status', 'active')
            ->whereIn('product_variant_id', $variantIds)
            ->get()
            ->keyBy('product_variant_id');

        $prices = collect();

        foreach ($items as $item) {
            $variantId = $item['product_variant_id'];
            $catalogEntry = $catalogEntries->get($variantId);

            if ($catalogEntry === null) {
                throw new InvalidArgumentException("Product variant ID {$variantId} is not in the vendor's active catalog.");
            }

            $prices->put($variantId, (float) $catalogEntry->price);
        }

        return $prices;
    }
}
