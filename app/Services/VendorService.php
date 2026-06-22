<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class VendorService
{
    /**
     * Whitelist of user-facing sort keys mapped to real DB columns.
     * Unknown keys fall back to a safe default column.
     */
    private const SORT_COLUMN_MAP = [
        'fullname' => 'fullname',
        'email' => 'email',
        'status' => 'status',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];

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
        $sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'created_at';
        $direction = in_array(mb_strtolower($orderDirection), ['asc', 'desc'], true) ? mb_strtolower($orderDirection) : 'asc';

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
            ->orderBy($sortColumn, $direction)
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
            throw new InvalidArgumentException('Cannot delete vendor: it has associated purchase orders.');
        }

        if ($vendor->hasCatalogEntries()) {
            throw new InvalidArgumentException('Cannot delete vendor: it has associated catalog entries.');
        }

        DB::transaction(fn () => $vendor->delete());
    }
}
