<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class BrandService
{
    /**
     * @return LengthAwarePaginator<int, Brand>
     */
    public function list(
        string $status = 'all',
        string $orderBy = 'name',
        string $orderDirection = 'asc',
        int $perPage = 20,
        ?string $filter = null,
    ): LengthAwarePaginator {
        return Brand::query()
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where('name', 'like', "%{$filter}%")
            )
            ->when($status === 'archived', fn ($q) => $q->onlyTrashed())
            ->withCount('products')
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Brand
    {
        return DB::transaction(function () use ($data): Brand {
            return Brand::create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Brand $brand, array $data): Brand
    {
        return DB::transaction(function () use ($brand, $data): Brand {
            $brand->update($data);

            return $brand;
        });
    }

    public function delete(Brand $brand): void
    {
        if ($brand->hasActiveProducts()) {
            throw new Exception('Cannot delete brand: it is assigned to one or more active products.');
        }

        DB::transaction(fn () => $brand->delete());
    }

    public function restore(Brand $brand): void
    {
        DB::transaction(fn () => $brand->restore());
    }
}
