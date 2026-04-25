<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @method list(ProductVariant $variant, ?string $type = null): Collection<int, ProductVariantUnit>
 */
final class ProductVariantUnitService
{
    /**
     * @return Collection<int, ProductVariantUnit>
     */
    public function list(ProductVariant $variant, ?string $type = null): Collection
    {
        return $variant->units()
            ->when($type, fn ($q) => $q->where('type', $type))
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(ProductVariant $variant, array $data): ProductVariantUnit
    {
        return DB::transaction(function () use ($variant, $data): ProductVariantUnit {
            return $variant->units()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProductVariantUnit $unit, array $data): ProductVariantUnit
    {
        return DB::transaction(function () use ($unit, $data): ProductVariantUnit {
            $unit->update($data);

            return $unit->refresh();
        });
    }

    public function delete(ProductVariantUnit $unit): void
    {
        DB::transaction(fn () => $unit->delete());
    }
}
