<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class ProductGalleryService
{
    /** @return LengthAwarePaginator<int, ProductVariant> */
    public function list(string $filter, ?int $brandId, ?int $categoryId, bool $available): LengthAwarePaginator
    {
        return $this->visibleQuery()
            ->when($filter !== '', function (Builder $query) use ($filter): void {
                $query->where(function (Builder $query) use ($filter): void {
                    $query->whereHas('product', fn (Builder $productQuery) => $productQuery->where('name', 'like', "%{$filter}%"))
                        ->orWhereHas('values', fn (Builder $valueQuery) => $valueQuery->where('value', 'like', "%{$filter}%"))
                        ->orWhere('identifier', 'like', "%{$filter}%")
                        ->orWhere('barcode', 'like', "%{$filter}%");
                });
            })
            ->when($brandId !== null, fn (Builder $query) => $query->whereHas('product', fn (Builder $productQuery) => $productQuery->whereKey($brandId)))
            ->when(
                $categoryId !== null,
                fn (Builder $query) => $query->whereHas('product.categories', fn (Builder $categoryQuery) => $categoryQuery->whereKey($categoryId)),
            )
            ->when($available, fn (Builder $query) => $query->whereHas('batches', $this->availableBatchConstraint(...)))
            ->orderBy('products.name')
            ->orderBy('product_variants.identifier')
            ->orderBy('product_variants.id')
            ->paginate(24)
            ->withQueryString();
    }

    public function find(int $variantId): ProductVariant
    {
        return $this->visibleQuery(detail: true)
            ->whereKey($variantId)
            ->firstOrFail();
    }

    /**
     * @return array{brands: array<int, array{id: int, name: string}>, categories: array<int, array{id: int, name: string}>}
     */
    public function filterOptions(): array
    {
        return [
            'brands' => Brand::query()
                ->whereHas('products', function (Builder $productQuery): void {
                    $productQuery
                        ->where('status', 'active')
                        ->whereHas('variants', fn (Builder $variantQuery) => $variantQuery->where('status', 'active'));
                })
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Brand $brand) => ['id' => $brand->id, 'name' => $brand->name])
                ->all(),
            'categories' => Category::query()
                ->whereHas('products', function (Builder $productQuery): void {
                    $productQuery
                        ->where('status', 'active')
                        ->whereHas('variants', fn (Builder $variantQuery) => $variantQuery->where('status', 'active'));
                })
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->name])
                ->all(),
        ];
    }

    /** @return Builder<ProductVariant> */
    private function visibleQuery(bool $detail = false): Builder
    {
        $relations = ['product.brand', 'product.measurementUnit', 'product.media', 'values.option', 'images'];

        if ($detail) {
            $relations[] = 'product.categories';
            $relations[] = 'activeSaleUnits';
        }

        return ProductVariant::query()
            ->select('product_variants.*')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->with($relations)
            ->withExists(['batches as is_available' => $this->availableBatchConstraint(...)])
            ->where('product_variants.status', 'active')
            ->where('products.status', 'active');
    }

    /** @param Builder<\App\Models\Batch> $batchQuery */
    private function availableBatchConstraint(Builder $batchQuery): void
    {
        $batchQuery
            ->active()
            ->where('remaining_quantity', '>', 0)
            ->where(fn (Builder $query) => $query->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today()));
    }
}
