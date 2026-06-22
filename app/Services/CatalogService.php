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
     * Whitelist of user-facing sort keys mapped to real DB columns.
     * Unknown keys fall back to a safe default column.
     */
    private const SORT_COLUMN_MAP = [
        'product_name' => 'products.name',
        'brand_name' => 'brands.name',
        'name' => 'products.name',
        'identifier' => 'product_variants.identifier',
        'vendor_count' => 'vendor_count',
        'price' => 'catalog.price',
        'status' => 'catalog.status',
        'created_at' => 'catalog.created_at',
        'updated_at' => 'catalog.updated_at',
        'id' => 'catalog.id',
    ];

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
        $sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'catalog.created_at';
        $direction = in_array(mb_strtolower($orderDirection), ['asc', 'desc'], true) ? mb_strtolower($orderDirection) : 'desc';
        $needsProductJoin = $sortColumn === 'products.name';

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
            ->orderBy($sortColumn, $direction)
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
        $sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'catalog.created_at';
        $direction = in_array(mb_strtolower($orderDirection), ['asc', 'desc'], true) ? mb_strtolower($orderDirection) : 'asc';
        $needsProductJoin = $sortColumn === 'products.name';

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
            ->orderBy($sortColumn, $direction)
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
                'product.measurementUnit',
                'product.categories',
                'values.option',
                'catalogEntries.vendor',
                'catalogEntries.unit',
            ])
            ->findOrFail($productVariantId);
    }

    /**
     * List all product variants with their catalog entries, regardless of
     * whether they have a vendor. Filters apply to the variant's status.
     *
     * @return LengthAwarePaginator<int, ProductVariant>
     */
    public function listVariants(
        string $status = 'active',
        string $orderBy = 'product_name',
        string $orderDirection = 'asc',
        int $perPage = 10,
        ?string $filter = null,
        ?int $vendorId = null,
    ): LengthAwarePaginator {
        $sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'product_variants.created_at';
        $direction = in_array(mb_strtolower($orderDirection), ['asc', 'desc'], true) ? mb_strtolower($orderDirection) : 'asc';
        $needsProductJoin = in_array($orderBy, ['product_name', 'brand_name'], true);
        $needsBrandJoin = $orderBy === 'brand_name';

        return ProductVariant::query()
            ->select('product_variants.*')
            ->when($needsProductJoin, fn ($q) => $q
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->whereNull('products.deleted_at'))
            ->when($needsBrandJoin, fn ($q) => $q
                ->leftJoin('brands', 'products.brand_id', '=', 'brands.id'))
            ->with([
                'product.brand',
                'product.measurementUnit',
                'values.option',
                'activePurchaseUnits',
                'catalogEntries.vendor',
                'catalogEntries.unit',
            ])
            ->addSelect(['vendor_count' => Catalog::selectRaw('count(distinct vendor_id)')
                ->whereColumn('catalog.product_variant_id', 'product_variants.id'),
            ])
            ->when($status !== 'all', fn ($q) => $q
                ->where('product_variants.status', $status))
            ->when($vendorId, fn ($q) => $q
                ->whereHas('catalogEntries', fn ($q) => $q->where('catalog.vendor_id', $vendorId)))
            ->when(
                $filter !== null && $filter !== '',
                fn ($q) => $q->where(function ($q) use ($filter): void {
                    $q->whereHas('product', fn ($pq) => $pq->where('name', 'like', "%{$filter}%"))
                        ->orWhere('product_variants.identifier', 'like', "%{$filter}%");
                })
            )
            ->orderBy($sortColumn, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function delete(Catalog $catalog): void
    {
        DB::transaction(fn () => $catalog->delete());
    }
}
