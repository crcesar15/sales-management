<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
    public function create(ProductVariant $variant, array $data, bool $replicateToSiblings = false): ProductVariantUnit
    {
        return DB::transaction(function () use ($variant, $data, $replicateToSiblings): ProductVariantUnit {
            if (! $replicateToSiblings) {
                return $variant->units()->create($data);
            }

            $variants = $this->lockedProductVariants($variant);
            $sourceVariant = $variants->firstOrFail(fn (ProductVariant $candidate): bool => $candidate->is($variant));
            $this->replicateToSiblings($variants, $sourceVariant, $data, $data['name']);

            return $sourceVariant->units()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ProductVariantUnit $unit, array $data, bool $replicateToSiblings = false): ProductVariantUnit
    {
        return DB::transaction(function () use ($unit, $data, $replicateToSiblings): ProductVariantUnit {
            if (! $replicateToSiblings) {
                $unit->update($data);

                return $unit->refresh();
            }

            $lockedUnit = ProductVariantUnit::query()->lockForUpdate()->findOrFail($unit->id);
            $sourceVariant = ProductVariant::query()->findOrFail($lockedUnit->product_variant_id);
            $variants = $this->lockedProductVariants($sourceVariant);
            $sourceVariant = $variants->firstOrFail(fn (ProductVariant $candidate): bool => $candidate->id === $lockedUnit->product_variant_id);
            $originalName = $lockedUnit->name;

            $this->replicateToSiblings($variants, $sourceVariant, $data, $originalName, $lockedUnit->type);
            $lockedUnit->update($data);

            return $lockedUnit->refresh();
        });
    }

    public function delete(ProductVariantUnit $unit): void
    {
        DB::transaction(fn () => $unit->delete());
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    private function lockedProductVariants(ProductVariant $variant): Collection
    {
        return ProductVariant::query()
            ->where('product_id', $variant->product_id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     * @param  array<string, mixed>  $data
     */
    private function replicateToSiblings(
        Collection $variants,
        ProductVariant $sourceVariant,
        array $data,
        string $originalName,
        ?string $type = null,
    ): void {
        $type ??= $data['type'];
        $siblingIds = $variants
            ->reject(fn (ProductVariant $candidate): bool => $candidate->is($sourceVariant))
            ->map(fn (ProductVariant $candidate): int => $candidate->id)
            ->all();

        if ($siblingIds === []) {
            return;
        }

        $unitsByVariant = ProductVariantUnit::query()
            ->whereIn('product_variant_id', $siblingIds)
            ->where('type', $type)
            ->lockForUpdate()
            ->get()
            ->groupBy('product_variant_id');

        foreach ($siblingIds as $siblingId) {
            /** @var Collection<int, ProductVariantUnit> $units */
            $units = $unitsByVariant->get($siblingId, collect());
            $matchingUnit = $units->firstWhere('name', $originalName);
            $conflictingUnit = $units->first(fn (ProductVariantUnit $candidate): bool => $candidate->name === $data['name'] && ! $candidate->is($matchingUnit));

            if ($conflictingUnit instanceof ProductVariantUnit) {
                throw new InvalidArgumentException('A sibling variant already has a different unit with this name.');
            }
        }

        foreach ($siblingIds as $siblingId) {
            /** @var Collection<int, ProductVariantUnit> $units */
            $units = $unitsByVariant->get($siblingId, collect());
            $matchingUnit = $units->firstWhere('name', $originalName);

            if ($matchingUnit instanceof ProductVariantUnit) {
                $matchingUnit->update($data);

                continue;
            }

            ProductVariantUnit::query()->create([
                ...$data,
                'product_variant_id' => $siblingId,
                'type' => $type,
            ]);
        }
    }
}
