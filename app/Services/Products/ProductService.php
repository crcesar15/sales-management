<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ProductService
{
    public function __construct(private readonly PendingMediaService $pendingMediaService) {}

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function list(
        string $status = 'all',
        ?string $filter = null,
        ?int $brandId = null,
        ?int $categoryId = null,
        string $orderBy = 'name',
        string $orderDirection = 'asc',
        int $perPage = 20,
    ): LengthAwarePaginator {
        return Product::query()
            ->with(['brand', 'measurementUnit', 'categories', 'media'])
            ->withCount('variants')
            ->when($filter, fn ($q) => $q->where('name', 'like', "%{$filter}%"))
            ->when($brandId, fn ($q) => $q->where('brand_id', $brandId))
            ->when($categoryId, fn ($q) => $q->whereHas('categories', fn ($q) => $q->where('categories.id', $categoryId)))
            ->when($status === 'active', fn ($q) => $q->where('status', 'active'))
            ->when($status === 'inactive', fn ($q) => $q->where('status', 'inactive'))
            ->when($status === 'archived', fn ($q) => $q->onlyTrashed())
            ->orderBy($orderBy, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $product = Product::create([
                'brand_id' => $data['brand_id'] ?? null,
                'measurement_unit_id' => $data['measurement_unit_id'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'],
            ]);

            if (isset($data['categories_ids'])) {
                $product->categories()->sync($data['categories_ids']);
            }

            if (! empty($data['pending_media_ids'])) {
                $this->pendingMediaService->commit($product, $data['pending_media_ids']);
            }

            ProductVariant::create([
                'product_id' => $product->id,
                'identifier' => null,
                'barcode' => $data['barcode'] ?? null,
                'price' => $data['price'],
                'stock' => $data['stock'],
                'status' => 'active',
            ]);

            return $product->load(['brand', 'measurementUnit', 'categories', 'media', 'variants']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $product->update([
                'brand_id' => $data['brand_id'] ?? $product->brand_id,
                'measurement_unit_id' => $data['measurement_unit_id'] ?? $product->measurement_unit_id,
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'status' => $data['status'] ?? $product->status,
            ]);

            if (array_key_exists('categories_ids', $data)) {
                $product->categories()->sync($data['categories_ids'] ?? []);
            }

            if (! empty($data['pending_media_ids'])) {
                $this->pendingMediaService->commit($product, $data['pending_media_ids']);
            }

            if (! empty($data['remove_media_ids'])) {
                foreach ($data['remove_media_ids'] as $mediaId) {
                    $product->deleteMedia($mediaId);
                }
            }

            return $product->load(['brand', 'measurementUnit', 'categories', 'media', 'variants']);
        });
    }

    public function delete(Product $product): void
    {
        if ($product->hasActiveVariants()) {
            throw new Exception('Cannot delete product: it has active variants.');
        }

        DB::transaction(fn () => $product->delete());
    }

    public function restore(int $id): void
    {
        DB::transaction(function () use ($id): void {
            Product::withTrashed()->findOrFail($id)->restore();
        });
    }
}
