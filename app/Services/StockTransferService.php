<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RolesEnum;
use App\Models\Batch;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class StockTransferService
{
    private const TRANSITION_MAP = [
        'requested' => ['picked'],
        'picked' => ['in_transit'],
        'in_transit' => ['received'],
        'received' => ['completed'],
    ];

    private readonly BatchService $batchService;

    public function __construct(BatchService $batchService)
    {
        $this->batchService = $batchService;
    }

    /**
     * @param  array{status?: string|null, from_store_id?: int|null, to_store_id?: int|null}  $filters
     * @return LengthAwarePaginator<int, StockTransfer>
     */
    public function list(array $filters, int $perPage, User $user): LengthAwarePaginator
    {
        $query = StockTransfer::query()
            ->with(['fromStore', 'toStore', 'requestedBy', 'items.productVariant.product.brand']);

        if (! $user->hasRole(RolesEnum::ADMIN->value)) {
            $storeIds = $user->stores()->pluck('stores.id');

            $query->where(function ($q) use ($storeIds): void {
                $q->whereIn('from_store_id', $storeIds)
                    ->orWhereIn('to_store_id', $storeIds);
            });
        }

        $query->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['from_store_id'] ?? null, fn ($q, $storeId) => $q->where('from_store_id', $storeId))
            ->when($filters['to_store_id'] ?? null, fn ($q, $storeId) => $q->where('to_store_id', $storeId))
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTransfer(array $data, User $actor): StockTransfer
    {
        return DB::transaction(function () use ($data, $actor): StockTransfer {
            $transfer = StockTransfer::create([
                'from_store_id' => $data['from_store_id'],
                'to_store_id' => $data['to_store_id'],
                'requested_by' => $actor->id,
                'status' => 'requested',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity_requested' => $item['quantity_requested'],
                ]);
            }

            activity()
                ->causedBy($actor)
                ->performedOn($transfer)
                ->log('created');

            return $transfer->load(['fromStore', 'toStore', 'requestedBy', 'items.productVariant']);
        });
    }

    /**
     * @param  array<int, array{id: int, quantity_sent?: int, quantity_received?: int}>|null  $itemQuantities
     */
    public function transitionStatus(
        StockTransfer $transfer,
        string $newStatus,
        User $actor,
        ?array $itemQuantities = null
    ): void {
        $this->validateTransition($transfer->status, $newStatus);

        if ($newStatus === 'completed') {
            $this->completeTransfer($transfer, $actor);

            return;
        }

        DB::transaction(function () use ($transfer, $newStatus, $actor, $itemQuantities): void {
            if ($newStatus === 'picked' && $itemQuantities) {
                $this->updateItemQuantities($transfer, $itemQuantities, 'quantity_sent');
            }

            if ($newStatus === 'received' && $itemQuantities) {
                $this->updateItemQuantities($transfer, $itemQuantities, 'quantity_received');
            }

            $transfer->update(['status' => $newStatus]);

            activity()
                ->causedBy($actor)
                ->performedOn($transfer)
                ->withProperties(['from' => $transfer->status, 'to' => $newStatus])
                ->log("Status changed to {$newStatus}");
        });
    }

    public function completeTransfer(StockTransfer $transfer, User $actor): void
    {
        if ($transfer->status !== 'received') {
            throw new InvalidArgumentException('Transfer must be in received status to complete.');
        }

        DB::transaction(function () use ($transfer, $actor): void {
            $transfer->load('items');

            foreach ($transfer->items as $item) {
                $this->batchService->deductFIFOForTransfer(
                    $item->product_variant_id,
                    $transfer->from_store_id,
                    $item->quantity_received,
                );

                Batch::create([
                    'product_variant_id' => $item->product_variant_id,
                    'reception_order_id' => null,
                    'store_id' => $transfer->to_store_id,
                    'initial_quantity' => $item->quantity_received,
                    'remaining_quantity' => $item->quantity_received,
                    'missing_quantity' => 0,
                    'sold_quantity' => 0,
                    'transferred_quantity' => 0,
                    'status' => 'queued',
                ]);
            }

            $transfer->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            activity()
                ->causedBy($actor)
                ->performedOn($transfer)
                ->log('completed');
        });
    }

    public function cancelTransfer(StockTransfer $transfer, ?string $reason, User $actor): void
    {
        if (in_array($transfer->status, ['completed', 'cancelled'], true)) {
            throw new InvalidArgumentException("Cannot cancel a transfer with status: {$transfer->status}.");
        }

        DB::transaction(function () use ($transfer, $reason, $actor): void {
            $transfer->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            activity()
                ->causedBy($actor)
                ->performedOn($transfer)
                ->withProperties(['reason' => $reason])
                ->log('cancelled');
        });
    }

    private function validateTransition(string $from, string $to): void
    {
        $allowed = self::TRANSITION_MAP[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new InvalidArgumentException("Invalid transition: {$from} → {$to}.");
        }
    }

    /**
     * @param  array<int, array{id: int, quantity_sent?: int, quantity_received?: int}>  $itemQuantities
     */
    private function updateItemQuantities(StockTransfer $transfer, array $itemQuantities, string $field): void
    {
        foreach ($itemQuantities as $itemData) {
            StockTransferItem::query()
                ->where('id', $itemData['id'])
                ->where('stock_transfer_id', $transfer->id)
                ->update([$field => $itemData[$field] ?? 0]);
        }
    }
}
