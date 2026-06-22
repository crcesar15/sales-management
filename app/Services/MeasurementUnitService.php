<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MeasurementUnit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class MeasurementUnitService
{
    /**
     * Whitelist of user-facing sort keys mapped to real DB columns.
     * Unknown keys fall back to a safe default column.
     */
    private const SORT_COLUMN_MAP = [
        'name' => 'name',
        'abbreviation' => 'abbreviation',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];

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
        $sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'created_at';
        $direction = in_array(mb_strtolower($orderDirection), ['asc', 'desc'], true) ? mb_strtolower($orderDirection) : 'asc';

        return MeasurementUnit::query()
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where('name', 'like', "%{$filter}%")
                    ->orWhere('abbreviation', 'like', "%{$filter}%")
            )
            ->when($status === 'all', fn ($q) => $q->withTrashed())
            ->when($status === 'archived', fn ($q) => $q->onlyTrashed())
            ->withCount('products')
            ->orderBy($sortColumn, $direction)
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
            throw new InvalidArgumentException('Cannot delete measurement unit: it is assigned to one or more active products.');
        }

        DB::transaction(fn () => $measurementUnit->delete());
    }

    public function restore(MeasurementUnit $measurementUnit): void
    {
        DB::transaction(fn () => $measurementUnit->restore());
    }
}
