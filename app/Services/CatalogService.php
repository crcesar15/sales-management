<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Catalog;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class CatalogService
{
    /**
     * @return LengthAwarePaginator<int, Catalog>
     */
    public function list(
        string $status = 'all',
        string $orderBy = 'created_at',
        string $orderDirection = 'desc',
        int $perPage = 20,
        ?string $filter = null,
        ?int $vendorId = null,
    ): LengthAwarePaginator {
        $needsProductJoin = $orderBy === 'product_name';

        return Catalog::query()
            ->select('catalog.*')
            ->when($needsProductJoin, fn ($q) => $q
                ->join('product_variants', 'catalog.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id'))
            ->with(['vendor', 'productVariant.product.brand', 'productVariant.product.measurementUnit', 'productVariant.values.option', 'productVariant.activePurchaseUnits', 'unit'])
            ->when($vendorId, fn ($q) => $q->where('catalog.vendor_id', $vendorId))
            ->when($status !== 'all', fn ($q) => $q->where('catalog.status', $status))
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where(function ($q) use ($filter): void {
                    $q->whereHas('productVariant.product', fn ($pq) => $pq->where('name', 'like', "%{$filter}%"))
                        ->orWhereHas('productVariant', fn ($vq) => $vq->where('identifier', 'like', "%{$filter}%"));
                })
            )
            ->orderBy($needsProductJoin ? 'products.name' : $orderBy, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return LengthAwarePaginator<int, Catalog>
     */
    public function listGroupedByProduct(
        string $status = 'active',
        string $orderBy = 'product_name',
        string $orderDirection = 'asc',
        int $perPage = 10,
        ?string $filter = null,
        ?int $vendorId = null,
    ): LengthAwarePaginator {
        $needsProductJoin = $orderBy === 'product_name';

        return Catalog::query()
            ->select('catalog.*')
            ->when($needsProductJoin, fn ($q) => $q
                ->join('product_variants', 'catalog.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id'))
            ->with(['vendor', 'productVariant.product.brand', 'productVariant.product.measurementUnit', 'productVariant.values.option', 'productVariant.activePurchaseUnits', 'unit'])
            ->when($vendorId, fn ($q) => $q->where('catalog.vendor_id', $vendorId))
            ->when($status !== 'all', fn ($q) => $q->where('catalog.status', $status))
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where(function ($q) use ($filter): void {
                    $q->whereHas('productVariant.product', fn ($pq) => $pq->where('name', 'like', "%{$filter}%"))
                        ->orWhereHas('productVariant', fn ($vq) => $vq->where('identifier', 'like', "%{$filter}%"));
                })
            )
            ->orderBy($needsProductJoin ? 'products.name' : $orderBy, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Catalog
    {
        return DB::transaction(function () use ($data): Catalog {
            $catalog = Catalog::create($data);

            return $catalog->load(['vendor', 'productVariant.product', 'productVariant.values.option', 'unit']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Catalog $catalog, array $data): Catalog
    {
        return DB::transaction(function () use ($catalog, $data): Catalog {
            $catalog->update($data);

            return $catalog->load(['vendor', 'productVariant.product', 'productVariant.values.option', 'unit']);
        });
    }

    public function getVariantWithCatalogEntries(int $productVariantId): ProductVariant
    {
        return ProductVariant::query()
            ->with([
                'product.brand',
                'values.option',
                'catalogEntries.vendor',
                'catalogEntries.unit',
            ])
            ->findOrFail($productVariantId);
    }

    public function delete(Catalog $catalog): void
    {
        DB::transaction(fn () => $catalog->delete());
    }
}
