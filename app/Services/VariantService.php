<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Catalog;
use App\Models\ProductVariant;
use Illuminate\Pagination\LengthAwarePaginator;

final class VariantService
{
    private const SORT_COLUMN_MAP = [
        'product_name' => 'products.name',
        'brand_name' => 'brands.name',
    ];

    /**
     * @param  array{
     *      includes:array<string>,
     *      vendor_id?: int,
     *      order_by: string,
     *      order_direction: string|null,
     *      filter: string|null,
     *      filter_by: string,
     *      status: string,
     *      page: int,
     *      per_page: int
     * }  $config
     * @return LengthAwarePaginator<int,ProductVariant>
     */
    public function getVariants(array $config): LengthAwarePaginator
    {
        $query = ProductVariant::query();
        $query->select(['product_variants.*']);

        // Always eager-load relationships needed by the resource
        $query->with(['product.brand', 'product.measurementUnit', 'values.option', 'activePurchaseUnits']);

        // Includes
        foreach ($config['includes'] as $include) {
            switch ($include) {
                case 'vendors':
                    $query->with('vendors');
                    break;
                case 'product':
                    $query->with('product.categories');
                    break;
            }
        }

        // Filter by vendor and eager-load the vendor relationship with pivot data
        if (isset($config['vendor_id']) && ! empty($config['vendor_id'])) {
            $vendorId = (int) $config['vendor_id'];
            $query->whereHas('vendors', fn ($q) => $q->where('vendors.id', $vendorId));
            $query->with(['vendors' => fn ($q) => $q->where('vendors.id', $vendorId)]);
        }

        // Determine if we need the products join for filtering/ordering
        $needsProductJoin = false;

        foreach ($config['includes'] as $include) {
            if ($include === 'product') {
                $needsProductJoin = true;
            }
        }

        $needsFilter = ! empty($config['filter']) && $config['filter_by'] === 'name';
        $needsStatusFilter = $config['status'] !== 'all';
        $needsOrderOnProduct = in_array($config['order_by'], ['name', 'product_name'], true);

        if ($needsProductJoin || $needsFilter || $needsStatusFilter || $needsOrderOnProduct) {
            $query->join('products', 'product_variants.product_id', '=', 'products.id');
        }

        // Filter by product status
        if ($needsStatusFilter) {
            $query->where('products.status', $config['status']);
        }

        // Filter by name or other fields
        if ($needsFilter) {
            $filter = '%' . $config['filter'] . '%';
            $query->where('products.name', 'like', $filter);
        } elseif (! empty($config['filter'])) {
            $filter = '%' . $config['filter'] . '%';
            $query->where($config['filter_by'], 'like', $filter);
        }

        // Order by
        $orderBy = in_array($config['order_by'], ['name', 'product_name'], true)
            ? 'products.name'
            : $config['order_by'];
        $orderDirection = $config['order_direction'] ?? 'ASC';
        $query->orderBy($orderBy, $orderDirection);

        // pagination
        $page = $config['page'];
        $perPage = $config['per_page'];

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @return LengthAwarePaginator<int, ProductVariant>
     */
    public function listAllVariants(
        string $status = 'all',
        string $filter = '',
        string $orderBy = 'created_at',
        string $orderDirection = 'desc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? "product_variants.{$orderBy}";
        $needsProductJoin = $orderBy === 'product_name' || $orderBy === 'brand_name';
        $needsBrandJoin = $orderBy === 'brand_name';

        return ProductVariant::query()
            ->select('product_variants.*')
            ->when($needsProductJoin, fn ($q) => $q->join(
                'products', 'product_variants.product_id', '=', 'products.id'
            ))
            ->when($needsBrandJoin, fn ($q) => $q->leftJoin(
                'brands', 'products.brand_id', '=', 'brands.id'
            ))
            ->with(['product.brand', 'product.measurementUnit', 'product.categories', 'values.option', 'images'])
            ->when($status !== 'all', fn ($q) => $q->where('product_variants.status', $status))
            ->when($filter, fn ($q) => $q->whereHas(
                'product',
                fn ($pq) => $pq->where('name', 'like', "%{$filter}%")
            ))
            ->orderBy($sortColumn, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get vendor catalog entries — one row per variant+unit combination.
     *
     * @return LengthAwarePaginator<int, Catalog>
     */
    public function getVendorCatalog(
        int $vendorId,
        string $status = 'all',
        ?string $filter = null,
        string $orderBy = 'product_name',
        string $orderDirection = 'ASC',
        int $page = 1,
        int $perPage = 10,
    ): LengthAwarePaginator {
        $query = Catalog::query()
            ->where('catalog.vendor_id', $vendorId)
            ->with([
                'productVariant.product.brand',
                'productVariant.product.measurementUnit',
                'productVariant.values.option',
                'productVariant.activePurchaseUnits',
                'unit',
            ]);

        if ($status !== 'all') {
            $query->where('catalog.status', $status);
        }

        // Filter by product name
        if ($filter) {
            $query->whereHas('productVariant.product', fn ($q) => $q->where('name', 'like', "%{$filter}%"));
        }

        // Join product_variants and products for ordering
        $sortColumn = match ($orderBy) {
            'product_name', 'name' => 'products.name',
            'price' => 'catalog.price',
            'status' => 'catalog.status',
            default => 'catalog.id',
        };

        if ($sortColumn === 'products.name') {
            $query->join('product_variants', 'catalog.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->select('catalog.*')
                ->orderBy($sortColumn, $orderDirection);
        } else {
            $query->select('catalog.*')
                ->orderBy($sortColumn, $orderDirection);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
