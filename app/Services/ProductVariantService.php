<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantOptionValue;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ProductVariantService
{
    /**
     * Generate variants from options data using Cartesian product.
     *
     * Only allowed when the product has exactly one variant (the default) with no option values.
     * The first combination is assigned to the existing default variant.
     *
     * @param  array<int, array{name: string, values: array<int, string>}>  $optionsData
     */
    public function generateVariants(Product $product, array $optionsData): void
    {
        DB::transaction(function () use ($product, $optionsData): void {
            $product->load('variants.values');

            $variants = $product->variants;

            $defaultVariant = $variants->firstOrFail();

            if ($variants->count() !== 1 || $defaultVariant->values()->exists()) {
                throw new Exception('Cannot generate variants: product already has variants configured.');
            }

            $valueIdArrays = [];

            foreach ($optionsData as $optionData) {
                $option = ProductOption::create([
                    'product_id' => $product->id,
                    'name' => $optionData['name'],
                ]);

                $createdValueIds = [];

                foreach ($optionData['values'] as $value) {
                    $createdValueIds[] = ProductOptionValue::create([
                        'product_option_id' => $option->id,
                        'value' => $value,
                    ])->id;
                }

                $valueIdArrays[] = $createdValueIds;
            }

            $combinations = $this->cartesianProduct($valueIdArrays);

            $defaultVariant->values()->sync($combinations[0]);

            foreach (array_slice($combinations, 1) as $combination) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'identifier' => null,
                    'barcode' => null,
                    'price' => $defaultVariant->price,
                    'stock' => 0,
                    'status' => 'active',
                ]);

                $variant->values()->sync($combination);
            }
        });
    }

    /**
     * Create options and variants from frontend-submitted data.
     *
     * Creates ProductOption + ProductOptionValue records, builds a name-to-ID
     * lookup map, then creates each variant with its specific price/stock/barcode.
     *
     * @param  array<int, array{name: string, values: array<int, string>}>  $optionsData
     * @param  array<int, array{option_values: array<string, string>, price: float|int, stock: int, barcode: string|null, pending_media_ids?: array<int, int>}>  $variantsData
     * @param  array<int, int>  $pendingMediaMap  Mapping of pending_media_uploads.id → media.id
     */
    public function createVariantsFromData(Product $product, array $optionsData, array $variantsData, array $pendingMediaMap = []): void
    {
        DB::transaction(function () use ($product, $optionsData, $variantsData, $pendingMediaMap): void {
            $valueLookup = [];

            foreach ($optionsData as $optionData) {
                $option = ProductOption::create([
                    'product_id' => $product->id,
                    'name' => $optionData['name'],
                ]);

                $valueLookup[$optionData['name']] = [];

                foreach ($optionData['values'] as $value) {
                    $created = ProductOptionValue::create([
                        'product_option_id' => $option->id,
                        'value' => $value,
                    ]);
                    $valueLookup[$optionData['name']][$value] = $created->id;
                }
            }

            foreach ($variantsData as $variantData) {
                $optionValueIds = [];

                foreach ($variantData['option_values'] as $optionName => $valueString) {
                    $optionValueIds[] = $valueLookup[$optionName][$valueString];
                }

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'identifier' => null,
                    'barcode' => $variantData['barcode'] ?? null,
                    'price' => $variantData['price'],
                    'stock' => $variantData['stock'],
                    'status' => 'active',
                ]);

                $variant->values()->sync($optionValueIds);

                if (! empty($variantData['pending_media_ids']) && ! empty($pendingMediaMap)) {
                    $mediaIds = [];
                    foreach ($variantData['pending_media_ids'] as $pendingId) {
                        if (isset($pendingMediaMap[$pendingId])) {
                            $mediaIds[] = $pendingMediaMap[$pendingId];
                        }
                    }

                    if (! empty($mediaIds)) {
                        $this->syncVariantImages($variant, $mediaIds);
                    }
                }
            }
        });
    }

    /**
     * Manually create a single variant with specific option values.
     *
     * @param  array<string, mixed>  $data
     */
    public function storeManual(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data): ProductVariant {
            $valueIds = $data['option_value_ids'];

            if ($this->isDuplicateCombination($product, $valueIds)) {
                throw new Exception('A variant with this combination of option values already exists.');
            }

            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'identifier' => $data['identifier'] ?? null,
                'barcode' => null,
                'price' => $data['price'],
                'stock' => $data['stock'],
                'status' => $data['status'] ?? 'active',
            ]);

            $variant->values()->sync($valueIds);

            return $variant;
        });
    }

    /**
     * Update variant fields (identifier, price, status only — stock is read-only).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data): ProductVariant {
            $updateData = collect($data)->only(['identifier', 'price', 'status'])->toArray();

            $variant->update($updateData);

            return $variant->refresh();
        });
    }

    /**
     * Hard delete a variant. CASCADE handles pivot cleanup.
     */
    public function destroy(ProductVariant $variant): void
    {
        DB::transaction(fn () => $variant->delete());
    }

    /**
     * Check if a variant with the exact same set of option value IDs already exists.
     *
     * @param  array<int, int>  $valueIds
     */
    public function isDuplicateCombination(Product $product, array $valueIds, ?int $excludeVariantId = null): bool
    {
        $combinationSize = count($valueIds);

        if ($combinationSize === 0) {
            return false;
        }

        return $product->variants()
            ->when($excludeVariantId, fn ($q) => $q->where('id', '!=', $excludeVariantId))
            ->whereHas('values', fn ($q) => $q->whereIn('product_option_values.id', $valueIds))
            ->withCount([
                'values as matching_count' => fn ($q) => $q->whereIn('product_option_values.id', $valueIds),
            ])
            ->having('matching_count', $combinationSize)
            ->exists();
    }

    /**
     * Sync media IDs on the variant's image pivot with position.
     *
     * @param  array<int, int>  $mediaIds
     */
    public function syncVariantImages(ProductVariant $variant, array $mediaIds): void
    {
        $syncData = [];

        foreach ($mediaIds as $index => $mediaId) {
            $syncData[$mediaId] = ['position' => $index];
        }

        $variant->images()->sync($syncData);
    }

    /**
     * Get variant images, falling back to product media if none are associated.
     *
     * @return Collection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media>
     */
    public function getVariantImages(ProductVariant $variant): Collection
    {
        $variant->load('images');

        if ($variant->images->isNotEmpty()) {
            return $variant->images;
        }

        $product = $variant->product()->firstOrFail();

        return $product->getMedia('images');
    }

    /**
     * Create an option with its values.
     *
     * @param  array<string, mixed>  $data
     */
    public function storeOption(Product $product, array $data): ProductOption
    {
        return DB::transaction(function () use ($product, $data): ProductOption {
            $option = ProductOption::create([
                'product_id' => $product->id,
                'name' => $data['name'],
            ]);

            foreach ($data['values'] as $value) {
                ProductOptionValue::create([
                    'product_option_id' => $option->id,
                    'value' => $value,
                ]);
            }

            return $option->load('values');
        });
    }

    /**
     * Rename an option.
     */
    public function updateOption(ProductOption $option, string $name): ProductOption
    {
        $option->update(['name' => $name]);

        return $option->refresh();
    }

    /**
     * Delete an option. CASCADE removes values and variant pivot rows.
     */
    public function destroyOption(ProductOption $option): void
    {
        DB::transaction(fn () => $option->delete());
    }

    /**
     * Create a single option value.
     */
    public function storeOptionValue(ProductOption $option, string $value): ProductOptionValue
    {
        return ProductOptionValue::create([
            'product_option_id' => $option->id,
            'value' => $value,
        ]);
    }

    /**
     * Delete an option value. Blocked if used by any variant.
     */
    public function destroyOptionValue(ProductOptionValue $value): void
    {
        $isInUse = ProductVariantOptionValue::where('product_option_value_id', $value->id)->exists();

        if ($isInUse) {
            throw new Exception('Cannot delete option value: it is used by one or more variants.');
        }

        DB::transaction(fn () => $value->delete());
    }

    /**
     * Compute the Cartesian product of arrays.
     *
     * @param  array<int, array<int, int>>  $arrays
     * @return array<int, array<int, int>>
     */
    private function cartesianProduct(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $values) {
            $append = [];

            foreach ($result as $product) {
                foreach ($values as $value) {
                    $append[] = [...$product, $value];
                }
            }

            $result = $append;
        }

        return $result;
    }
}
