<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\VariantStoreLocation;
use Illuminate\Support\Facades\DB;

final class VariantLocationService
{
    /** @param list<array{store_id: int, location_code: string|null}> $locations */
    public function update(ProductVariant $variant, array $locations, bool $replicate = false): void
    {
        DB::transaction(function () use ($variant, $locations, $replicate): void {
            $variants = ProductVariant::query()
                ->when($replicate, fn ($query) => $query->where('product_id', $variant->product_id), fn ($query) => $query->whereKey($variant->id))
                ->orderBy('id')->lockForUpdate()->get();

            foreach ($variants as $target) {
                $this->updateLocations($target, $target->id === $variant->id ? $locations : array_values(array_filter(
                    $locations,
                    fn (array $row): bool => $row['location_code'] !== null,
                )));
            }
        });
    }

    /** @return list<string> */
    public function suggestions(int $storeId, string $search): array
    {
        $prefix = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtoupper(mb_trim($search)));

        return array_values(VariantStoreLocation::query()->where('store_id', $storeId)
            ->whereRaw("location_code like ? escape '!'", [$prefix . '%'])->distinct()->orderBy('location_code')
            ->limit(20)->get(['location_code'])->map(fn (VariantStoreLocation $location): string => $location->location_code)->all());
    }

    /** @param list<array{store_id: int, location_code: string|null}> $locations */
    private function updateLocations(ProductVariant $variant, array $locations): void
    {
        foreach ($locations as $row) {
            $location = $variant->storeLocations()->where('store_id', $row['store_id'])->first();
            if ($row['location_code'] === null) {
                $location?->delete();
            } elseif ($location !== null) {
                $location->update(['location_code' => $row['location_code']]);
            } else {
                $variant->storeLocations()->create($row);
            }
        }
    }
}
