<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Catalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CatalogService
{
    /**
     * @return LengthAwarePaginator<int, Catalog>
     */
    public function list(
        string $status = 'all',
        string $orderBy = 'created_at',
        string $orderDirection = 'desc',
        int $perPage = 20,
        ?string $filter = null,
        ?int $vendorId = null,
    ): LengthAwarePaginator {
        return Catalog::query()
            ->with(['vendor', 'productVariant.product', 'unit'])
            ->when($vendorId, fn ($q) => $q->where('vendor_id', $vendorId))
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where(function ($q) use ($filter): void {
                    $q->whereHas('productVariant.product', fn ($pq) => $pq->where('name', 'like', "%{$filter}%"))
                        ->orWhereHas('productVariant', fn ($vq) => $vq->where('identifier', 'like', "%{$filter}%"));
                })
            )
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Catalog
    {
        return DB::transaction(function () use ($data): Catalog {
            $catalog = Catalog::create($data);

            return $catalog->load(['vendor', 'productVariant.product', 'unit']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Catalog $catalog, array $data): Catalog
    {
        return DB::transaction(function () use ($catalog, $data): Catalog {
            $catalog->update($data);

            return $catalog->load(['vendor', 'productVariant.product', 'unit']);
        });
    }

    public function delete(Catalog $catalog): void
    {
        DB::transaction(fn () => $catalog->delete());
    }
}
