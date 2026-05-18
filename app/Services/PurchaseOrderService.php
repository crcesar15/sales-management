<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderProduct;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class PurchaseOrderService
{
    private const TRANSITION_MAP = [
        'draft' => ['awaiting_approval', 'cancelled'],
        'awaiting_approval' => ['approved', 'cancelled'],
        'approved' => ['sent', 'cancelled'],
        'sent' => ['partially_received', 'received', 'cancelled'],
        'partially_received' => ['received'],
        'received' => [],
    ];

    /**
     * @param  array{status?: string|null, vendor_id?: int|null, from?: string|null, to?: string|null}  $filters
     * @return LengthAwarePaginator<int, PurchaseOrder>
     */
    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return PurchaseOrder::query()
            ->with(['vendor', 'user', 'lineItems.productVariant.product'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['vendor_id'] ?? null, fn ($q, $vendorId) => $q->where('vendor_id', $vendorId))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('order_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('order_date', '<=', $to))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $actor): PurchaseOrder {
            $vendorId = $data['vendor_id'];
            $items = $data['items'];

            $po = PurchaseOrder::create([
                'user_id' => $actor->id,
                'vendor_id' => $vendorId,
                'status' => 'draft',
                'order_date' => $data['order_date'],
                'expected_arrival_date' => $data['expected_arrival_date'] ?? null,
                'discount' => $data['discount'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'sub_total' => 0,
                'total' => 0,
            ]);

            foreach ($items as $item) {
                $price = (float) $item['price'];
                $quantity = (float) $item['quantity'];
                $lineTotal = $price * $quantity;

                PurchaseOrderProduct::create([
                    'purchase_order_id' => $po->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $lineTotal,
                ]);
            }

            $this->recalculate($po);

            activity()
                ->causedBy($actor)
                ->performedOn($po)
                ->log('created');

            return $po->load(['vendor', 'user', 'lineItems.productVariant.product']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PurchaseOrder $po, array $data, User $actor): PurchaseOrder
    {
        if ($po->status !== 'draft') {
            throw new InvalidArgumentException('Only draft purchase orders can be updated.');
        }

        return DB::transaction(function () use ($po, $data, $actor): PurchaseOrder {
            $items = $data['items'] ?? null;
            $vendorId = $data['vendor_id'] ?? $po->vendor_id;

            if ($items !== null) {
                $po->lineItems()->delete();

                foreach ($items as $item) {
                    $price = (float) $item['price'];
                    $quantity = (float) $item['quantity'];
                    $lineTotal = $price * $quantity;

                    PurchaseOrderProduct::create([
                        'purchase_order_id' => $po->id,
                        'product_variant_id' => $item['product_variant_id'],
                        'quantity' => $quantity,
                        'price' => $price,
                        'total' => $lineTotal,
                    ]);
                }
            }

            $po->update([
                'vendor_id' => $vendorId,
                'order_date' => $data['order_date'] ?? $po->order_date,
                'expected_arrival_date' => $data['expected_arrival_date'] ?? $po->expected_arrival_date,
                'discount' => $data['discount'] ?? $po->discount,
                'notes' => $data['notes'] ?? $po->notes,
            ]);

            $this->recalculate($po);

            activity()
                ->causedBy($actor)
                ->performedOn($po)
                ->log('updated');

            return $po->load(['vendor', 'user', 'lineItems.productVariant.product']);
        });
    }

    public function transitionStatus(PurchaseOrder $po, string $newStatus, User $actor): void
    {
        $this->validateTransition($po->status, $newStatus);

        DB::transaction(function () use ($po, $newStatus, $actor): void {
            $oldStatus = $po->status;

            $po->update(['status' => $newStatus]);

            activity()
                ->causedBy($actor)
                ->performedOn($po)
                ->withProperties(['from' => $oldStatus, 'to' => $newStatus])
                ->log("Status changed to {$newStatus}");
        });
    }

    public function cancel(PurchaseOrder $po, ?string $reason, User $actor): void
    {
        $this->validateTransition($po->status, 'cancelled');

        DB::transaction(function () use ($po, $reason, $actor): void {
            $po->update(['status' => 'cancelled']);

            activity()
                ->causedBy($actor)
                ->performedOn($po)
                ->withProperties(['reason' => $reason])
                ->log('cancelled');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function markAsPaid(PurchaseOrder $po, array $data, User $actor): void
    {
        if ($po->is_paid) {
            throw new InvalidArgumentException('This purchase order is already marked as paid.');
        }

        $allowedStatuses = ['approved', 'sent', 'partially_received'];

        if (! in_array($po->status, $allowedStatuses, true)) {
            throw new InvalidArgumentException("Cannot mark purchase order with status {$po->status} as paid.");
        }

        DB::transaction(function () use ($po, $data, $actor): void {
            $po->update([
                'is_paid' => true,
                'paid_at' => now(),
                'proof_of_payment_type' => $data['proof_of_payment_type'],
                'proof_of_payment_number' => $data['proof_of_payment_number'],
            ]);

            activity()
                ->causedBy($actor)
                ->performedOn($po)
                ->withProperties([
                    'proof_of_payment_type' => $data['proof_of_payment_type'],
                    'proof_of_payment_number' => $data['proof_of_payment_number'],
                ])
                ->log('marked as paid');
        });
    }

    public function recalculate(PurchaseOrder $po): void
    {
        $po->refresh();

        $subTotal = $po->lineItems->sum('total');
        $discount = (float) $po->discount;
        $total = $subTotal - $discount;

        $po->update([
            'sub_total' => $subTotal,
            'total' => $total,
        ]);
    }

    /**
     * Update the purchase order's status based on reception progress.
     * Called after a reception order is completed or cancelled.
     */
    public function updateReceptionStatus(PurchaseOrder $po): void
    {
        $po->loadMissing('lineItems.receptionOrderItems.receptionOrder');

        if ($po->is_fully_received) {
            $newStatus = 'received';
        } elseif ($po->is_partially_received) {
            $newStatus = 'partially_received';
        } else {
            return;
        }

        if ($po->status === $newStatus) {
            return;
        }

        $this->validateTransition($po->status, $newStatus);

        $oldStatus = $po->status;
        $po->update(['status' => $newStatus]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($po)
            ->withProperties(['from' => $oldStatus, 'to' => $newStatus])
            ->log("Status changed to {$newStatus}");
    }

    private function validateTransition(string $from, string $to): void
    {
        $allowed = self::TRANSITION_MAP[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new InvalidArgumentException("Invalid transition: {$from} → {$to}.");
        }
    }
}
