<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RolesEnum;
use App\Models\Batch;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class StockAdjustmentService
{
    private readonly BatchService $batchService;

    public function __construct(BatchService $batchService)
    {
        $this->batchService = $batchService;
    }

    /**
     * @param  array{store_id?: int|null, reason?: string|null, date_from?: string|null, date_to?: string|null}  $filters
     * @return LengthAwarePaginator<int, StockAdjustment>
     */
    public function list(array $filters, int $perPage, User $user): LengthAwarePaginator
    {
        $query = StockAdjustment::query()
            ->with(['productVariant.product.brand', 'store', 'user', 'batch']);

        if (! $user->hasRole(RolesEnum::ADMIN->value)) {
            $query->where('user_id', $user->id);
        }

        $query->when($filters['store_id'] ?? null, fn ($q, $storeId) => $q->where('store_id', $storeId))
            ->when($filters['reason'] ?? null, fn ($q, $reason) => $q->where('reason', $reason))
            ->when($filters['date_from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', $from))
            ->when($filters['date_to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', $to))
            ->orderBy('created_at', 'desc');

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function apply(array $data, User $actor): StockAdjustment
    {
        return DB::transaction(function () use ($data, $actor): StockAdjustment {
            $variantId = $data['product_variant_id'];
            $storeId = $data['store_id'];
            $delta = $data['quantity_change'];

            $batch = $this->resolveBatch(
                $data['batch_id'] ?? null,
                $variantId,
                $storeId,
                $delta,
            );

            if ($delta < 0 && ($batch->remaining_quantity + $delta) < 0) {
                throw new InvalidArgumentException(
                    "Insufficient stock. Available: {$batch->remaining_quantity}, requested deduction: " . abs($delta) . '.'
                );
            }

            $this->batchService->activateIfQueued($batch);

            if (! $batch->wasRecentlyCreated) {
                $batch->increment('remaining_quantity', $delta);
            }

            $batch->refresh();
            if ($batch->remaining_quantity === 0) {
                $batch->update(['status' => 'closed']);
            }

            $adjustment = StockAdjustment::create([
                'product_variant_id' => $variantId,
                'store_id' => $storeId,
                'user_id' => $actor->id,
                'batch_id' => $batch->id,
                'quantity_change' => $delta,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
            ]);

            activity()
                ->causedBy($actor)
                ->performedOn($adjustment)
                ->withProperties([
                    'reason' => $data['reason'],
                    'delta' => $delta,
                    'batch_id' => $batch->id,
                ])
                ->log('created');

            return $adjustment->load(['productVariant.product.brand', 'store', 'user', 'batch']);
        });
    }

    public function getDetail(StockAdjustment $adjustment): StockAdjustment
    {
        return $adjustment->load([
            'productVariant.product.brand',
            'productVariant.values.option',
            'store',
            'user',
            'batch',
        ]);
    }

    private function resolveBatch(?int $batchId, int $variantId, int $storeId, int $delta): Batch
    {
        if ($batchId !== null) {
            return Batch::query()
                ->where('id', $batchId)
                ->where('product_variant_id', $variantId)
                ->where('store_id', $storeId)
                ->activeOrQueued()
                ->lockForUpdate()
                ->firstOrFail();
        }

        $batch = Batch::query()
            ->available($variantId, $storeId)
            ->lockForUpdate()
            ->first();

        if ($batch !== null) {
            return $batch;
        }

        if ($delta > 0) {
            return Batch::create([
                'product_variant_id' => $variantId,
                'reception_order_id' => null,
                'store_id' => $storeId,
                'initial_quantity' => $delta,
                'remaining_quantity' => $delta,
                'missing_quantity' => 0,
                'sold_quantity' => 0,
                'transferred_quantity' => 0,
                'status' => 'active',
            ]);
        }

        throw new InvalidArgumentException('No active batch found for this product variant in the specified store.');
    }
}
