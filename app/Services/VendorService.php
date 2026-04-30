<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Vendor;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class VendorService
{
    /**
     * @return LengthAwarePaginator<int, Vendor>
     */
    public function list(
        string $status = 'all',
        string $orderBy = 'fullname',
        string $orderDirection = 'asc',
        int $perPage = 20,
        ?string $filter = null,
    ): LengthAwarePaginator {
        return Vendor::query()
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where(function ($q) use ($filter): void {
                    $q->where('fullname', 'like', "%{$filter}%")
                        ->orWhere('email', 'like', "%{$filter}%");
                })
            )
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->withCount(['variants', 'purchaseOrders'])
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Vendor
    {
        return DB::transaction(function () use ($data): Vendor {
            return Vendor::create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Vendor $vendor, array $data): Vendor
    {
        return DB::transaction(function () use ($vendor, $data): Vendor {
            $vendor->update($data);

            return $vendor;
        });
    }

    public function delete(Vendor $vendor): void
    {
        if ($vendor->hasPurchaseOrders()) {
            throw new Exception('Cannot delete vendor: it has associated purchase orders.');
        }

        if ($vendor->hasCatalogEntries()) {
            throw new Exception('Cannot delete vendor: it has associated catalog entries.');
        }

        DB::transaction(fn () => $vendor->delete());
    }
}
