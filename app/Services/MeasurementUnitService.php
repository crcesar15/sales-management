<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MeasurementUnit;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class MeasurementUnitService
{
    /**
     * @return LengthAwarePaginator<int, MeasurementUnit>
     */
    public function list(
        string $status = 'all',
        string $orderBy = 'name',
        string $orderDirection = 'asc',
        int $perPage = 20,
        ?string $filter = null,
    ): LengthAwarePaginator {
        return MeasurementUnit::query()
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('abbreviation', 'like', "%{$filter}%")
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
    public function create(array $data): MeasurementUnit
    {
        return DB::transaction(function () use ($data): MeasurementUnit {
            return MeasurementUnit::create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MeasurementUnit $measurementUnit, array $data): MeasurementUnit
    {
        return DB::transaction(function () use ($measurementUnit, $data): MeasurementUnit {
            $measurementUnit->update($data);

            return $measurementUnit;
        });
    }

    public function delete(MeasurementUnit $measurementUnit): void
    {
        if ($measurementUnit->hasActiveProducts()) {
            throw new Exception('Cannot delete measurement unit: it is assigned to one or more active products.');
        }

        DB::transaction(fn () => $measurementUnit->delete());
    }

    public function restore(MeasurementUnit $measurementUnit): void
    {
        DB::transaction(fn () => $measurementUnit->restore());
    }
}
