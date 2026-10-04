<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderProduct;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class PurchaseOrderService
{
    private const TRANSITION_MAP = [
        'draft' => ['approved', 'cancelled'],
        'approved' => ['closed', 'cancelled'],
        'closed' => [],
        'cancelled' => [],
    ];

    /**
     * @param  array{status?: string|null, vendor_id?: int|null, from?: string|null, to?: string|null, is_paid?: bool|null}  $filters
     * @return LengthAwarePaginator<int, PurchaseOrder>
     */
    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        $isPaid = $filters['is_paid'] ?? null;

        return PurchaseOrder::query()
            ->with(['vendor', 'user', 'lineItems.productVariant.product'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['vendor_id'] ?? null, fn ($q, $vendorId) => $q->where('vendor_id', $vendorId))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('order_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('order_date', '<=', $to))
            ->when($isPaid !== null, fn ($q) => $q->where('is_paid', $isPaid))
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
                'status' => PurchaseOrderStatus::DRAFT,
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
                    'catalog_id' => $item['catalog_id'] ?? null,
                    'unit_id' => $item['unit_id'] ?? null,
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
        return DB::transaction(function () use ($po, $data, $actor): PurchaseOrder {
            $po = PurchaseOrder::query()->lockForUpdate()->whereKey($po->id)->sole();

            if ($po->status !== PurchaseOrderStatus::DRAFT) {
                throw new InvalidArgumentException('Only draft purchase orders can be updated.');
            }

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
                        'catalog_id' => $item['catalog_id'] ?? null,
                        'unit_id' => $item['unit_id'] ?? null,
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

    public function approve(PurchaseOrder $po, User $actor): void
    {
        $this->transitionStatus($po, PurchaseOrderStatus::APPROVED, $actor);
    }

    public function close(PurchaseOrder $po, ?string $completionNotes, User $actor): void
    {
        $this->transitionStatus($po, PurchaseOrderStatus::CLOSED, $actor, $completionNotes);
    }

    public function cancel(PurchaseOrder $po, ?string $reason, User $actor): void
    {
        DB::transaction(function () use ($po, $reason, $actor): void {
            $po = PurchaseOrder::query()->lockForUpdate()->whereKey($po->id)->sole();

            if ($po->is_paid) {
                throw new InvalidArgumentException('Paid purchase orders cannot be cancelled without a payment reversal.');
            }

            if ($po->receptionOrders()->where('status', 'completed')->exists()) {
                throw new InvalidArgumentException('Purchase orders with completed receptions cannot be cancelled.');
            }

            if ($po->receptionOrders()->where('status', 'pending')->exists()) {
                throw new InvalidArgumentException('Cancel pending reception orders before cancelling this purchase order.');
            }

            $this->validateTransition($po->status, PurchaseOrderStatus::CANCELLED);
            $oldStatus = $po->status;
            $po->update(['status' => PurchaseOrderStatus::CANCELLED]);

            activity()
                ->causedBy($actor)
                ->performedOn($po)
                ->withProperties(['from' => $oldStatus->value, 'to' => PurchaseOrderStatus::CANCELLED->value, 'reason' => $reason])
                ->log('cancelled');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function markAsPaid(PurchaseOrder $po, array $data, User $actor): void
    {
        DB::transaction(function () use ($po, $data, $actor): void {
            $po = PurchaseOrder::query()->lockForUpdate()->whereKey($po->id)->sole();

            if ($po->is_paid) {
                throw new InvalidArgumentException('This purchase order is already marked as paid.');
            }

            if (! in_array($po->status, [PurchaseOrderStatus::APPROVED, PurchaseOrderStatus::CLOSED], true)) {
                throw new InvalidArgumentException("Cannot mark purchase order with status {$po->status->value} as paid.");
            }

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
     * Close the purchase order when completed receptions fulfill every line item.
     * Called from the reception-completion transaction.
     */
    public function updateReceptionStatus(PurchaseOrder $po, User $actor): void
    {
        $po->loadMissing('lineItems.receptionOrderItems.receptionOrder');

        if (! $po->is_fully_received || $po->status === PurchaseOrderStatus::CLOSED) {
            return;
        }

        $this->transitionStatus($po, PurchaseOrderStatus::CLOSED, $actor);
    }

    private function transitionStatus(PurchaseOrder $po, PurchaseOrderStatus $newStatus, User $actor, ?string $completionNotes = null): void
    {
        DB::transaction(function () use ($po, $newStatus, $actor, $completionNotes): void {
            $po = PurchaseOrder::query()->lockForUpdate()->whereKey($po->id)->sole();
            $po->loadMissing('lineItems.receptionOrderItems.receptionOrder');
            $this->validateTransition($po->status, $newStatus);

            if ($newStatus === PurchaseOrderStatus::CLOSED) {
                if (! $po->is_fully_received && blank($completionNotes)) {
                    throw new InvalidArgumentException('A reason is required when closing a purchase order before all items have been fully received.');
                }

                if ($po->receptionOrders()->where('status', 'pending')->exists()) {
                    throw new InvalidArgumentException('Cancel pending reception orders before closing this purchase order.');
                }
            }

            $oldStatus = $po->status;
            $updateData = ['status' => $newStatus];

            if ($newStatus === PurchaseOrderStatus::CLOSED && $completionNotes !== null) {
                $updateData['completion_notes'] = $completionNotes;
            }

            $po->update($updateData);

            $properties = ['from' => $oldStatus->value, 'to' => $newStatus->value];
            if ($completionNotes !== null && $completionNotes !== '') {
                $properties['completion_notes'] = $completionNotes;
            }

            activity()
                ->causedBy($actor)
                ->performedOn($po)
                ->withProperties($properties)
                ->log("Status changed to {$newStatus->value}");
        });
    }

    private function validateTransition(PurchaseOrderStatus $from, PurchaseOrderStatus $to): void
    {
        $allowed = self::TRANSITION_MAP[$from->value];

        if (! in_array($to->value, $allowed, true)) {
            throw new InvalidArgumentException("Invalid transition: {$from->value} → {$to->value}.");
        }
    }
}
