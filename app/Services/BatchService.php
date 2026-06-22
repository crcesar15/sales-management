<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class BatchService
{
    public function __construct(private readonly FifoStockDeductionService $fifoStockDeductionService) {}

    /**
     * @param  array{status?: string|null, store_id?: int|null, product_variant_id?: int|null, expiry_from?: string|null, expiry_to?: string|null, expiring_soon?: bool, product_name?: string|null}  $filters
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
            ->when($filters['product_name'] ?? null, fn ($q, $name) => $q->whereHas(
                'productVariant.product',
                fn ($q) => $q->where('name', 'like', "%{$name}%")
            ))
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

            $variant = ProductVariant::find($batch->product_variant_id);

            if ($variant === null) {
                throw new InvalidArgumentException("Product variant ID {$batch->product_variant_id} not found.");
            }

            $variant->recalculateStock();

            activity()
                ->causedBy($actor)
                ->performedOn($batch)
                ->withProperties(['notes' => $notes])
                ->log('closed');
        });
    }

    /**
     * @param  array{batch_identifier?: string|null, expiry_date?: string|null}  $data
     */
    public function update(Batch $batch, array $data, User $actor): Batch
    {
        if ($batch->status === 'closed') {
            throw new InvalidArgumentException('Cannot update a closed batch.');
        }

        return DB::transaction(function () use ($batch, $data, $actor): Batch {
            $batch->update($data);

            activity()
                ->causedBy($actor)
                ->performedOn($batch)
                ->withProperties($batch->getChanges())
                ->log('updated');

            return $batch->load([
                'productVariant.product.brand',
                'productVariant.values.option',
                'store',
                'receptionOrder',
            ]);
        });
    }

    public function deductFIFOForTransfer(int $variantId, int $storeId, int $quantity): void
    {
        $this->fifoStockDeductionService->deductForTransfer($variantId, $storeId, $quantity);
    }
}
