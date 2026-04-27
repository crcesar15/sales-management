<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class BatchService
{
    /**
     * @param  array{status?: string|null, store_id?: int|null, product_variant_id?: int|null, expiry_from?: string|null, expiry_to?: string|null, expiring_soon?: bool}  $filters
     * @return LengthAwarePaginator<int, Batch>
     */
    public function list(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Batch::query()
            ->with(['productVariant.product.brand', 'store', 'receptionOrder'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['store_id'] ?? null, fn ($q, $storeId) => $q->where('store_id', $storeId))
            ->when($filters['product_variant_id'] ?? null, fn ($q, $variantId) => $q->where('product_variant_id', $variantId))
            ->when($filters['expiry_from'] ?? null, fn ($q, $from) => $q->where('expiry_date', '>=', $from))
            ->when($filters['expiry_to'] ?? null, fn ($q, $to) => $q->where('expiry_date', '<=', $to))
            ->when($filters['expiring_soon'] ?? false, function ($q): void {
                $days = (int) Setting::get('expiry_alert_days', 30);
                $q->expiringSoon($days);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getDetail(Batch $batch): Batch
    {
        return $batch->load([
            'productVariant.product.brand',
            'productVariant.values.option',
            'store',
            'receptionOrder',
        ]);
    }

    public function closeBatch(Batch $batch, ?string $notes, User $actor): void
    {
        if ($batch->status === 'closed') {
            throw new InvalidArgumentException('Batch is already closed.');
        }

        DB::transaction(function () use ($batch, $notes, $actor): void {
            $batch->update(['status' => 'closed']);

            activity()
                ->causedBy($actor)
                ->performedOn($batch)
                ->withProperties(['notes' => $notes])
                ->log('closed');
        });
    }

    public function deductFIFO(int $variantId, int $storeId, int $quantity): void
    {
        DB::transaction(function () use ($variantId, $storeId, $quantity): void {
            $remaining = $quantity;

            $batches = $this->getAvailableBatches($variantId, $storeId);

            $totalAvailable = $batches->sum('remaining_quantity');

            if ($totalAvailable < $quantity) {
                throw new RuntimeException("Insufficient stock. Available: {$totalAvailable}, requested: {$quantity}.");
            }

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $this->activateIfQueued($batch);

                $deduct = min($batch->remaining_quantity, $remaining);
                $batch->increment('sold_quantity', $deduct);
                $batch->decrement('remaining_quantity', $deduct);
                $remaining -= $deduct;

                $batch->refresh();
                if ($batch->remaining_quantity === 0) {
                    $batch->update(['status' => 'closed']);
                }
            }
        });
    }

    public function deductFIFOForTransfer(int $variantId, int $storeId, int $quantity): void
    {
        DB::transaction(function () use ($variantId, $storeId, $quantity): void {
            $remaining = $quantity;

            $batches = $this->getAvailableBatches($variantId, $storeId);
            $totalAvailable = $batches->sum('remaining_quantity');

            if ($totalAvailable < $quantity) {
                throw new RuntimeException("Insufficient stock. Available: {$totalAvailable}, requested: {$quantity}.");
            }

            foreach ($batches as $batch) {
                if ($remaining <= 0) {
                    break;
                }

                $this->activateIfQueued($batch);

                $deduct = min($batch->remaining_quantity, $remaining);
                $batch->increment('transferred_quantity', $deduct);
                $batch->decrement('remaining_quantity', $deduct);
                $remaining -= $deduct;

                $batch->refresh();
                if ($batch->remaining_quantity === 0) {
                    $batch->update(['status' => 'closed']);
                }
            }
        });
    }

    public function activateIfQueued(Batch $batch): void
    {
        if ($batch->status === 'queued') {
            $batch->update(['status' => 'active']);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Batch>
     */
    private function getAvailableBatches(int $variantId, int $storeId): \Illuminate\Database\Eloquent\Collection
    {
        return Batch::query()
            ->available($variantId, $storeId)
            ->lockForUpdate()
            ->get();
    }
}
