<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Catalog;
use App\Models\ProductVariant;
use Illuminate\Pagination\LengthAwarePaginator;

final class VariantService
{
    /**
     * Whitelist of user-facing sort keys mapped to real DB columns.
     * Unknown keys fall back to a safe default column.
     */
    private const SORT_COLUMN_MAP = [
        'product_name' => 'products.name',
        'name' => 'products.name',
        'brand_name' => 'brands.name',
        'identifier' => 'product_variants.identifier',
        'barcode' => 'product_variants.barcode',
        'price' => 'product_variants.price',
        'status' => 'product_variants.status',
        'created_at' => 'product_variants.created_at',
        'updated_at' => 'product_variants.updated_at',
        'catalog_price' => 'catalog.price',
        'catalog_status' => 'catalog.status',
        'catalog_id' => 'catalog.id',
    ];

    /**
     * Columns in the map that require the products join.
     */
    private const PRODUCT_JOIN_KEYS = ['product_name', 'name', 'brand_name', 'price', 'identifier'];

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
        $sortColumn = self::SORT_COLUMN_MAP[$config['order_by']] ?? 'product_variants.created_at';
        $needsOrderOnProduct = in_array($config['order_by'], self::PRODUCT_JOIN_KEYS, true);

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

        // Order by — whitelist validated above; direction normalized to asc/desc.
        $rawDirection = $config['order_direction'] ?? 'asc';
        $direction = in_array(mb_strtolower((string) $rawDirection), ['asc', 'desc'], true) ? mb_strtolower((string) $rawDirection) : 'asc';
        $query->orderBy($sortColumn, $direction);

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
        $sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'product_variants.created_at';
        $direction = in_array(mb_strtolower($orderDirection), ['asc', 'desc'], true) ? mb_strtolower($orderDirection) : 'asc';
        $needsProductJoin = in_array($orderBy, ['product_name', 'brand_name'], true);
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
            ->orderBy($sortColumn, $direction)
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

        // Sort column whitelist (catalog-scoped keys + shared product keys).
        $catalogMap = [
            'product_name' => 'products.name',
            'name' => 'products.name',
            'price' => 'catalog.price',
            'status' => 'catalog.status',
            'catalog_status' => 'catalog.status',
            'catalog_price' => 'catalog.price',
            'catalog_id' => 'catalog.id',
            'id' => 'catalog.id',
        ];
        $sortColumn = $catalogMap[$orderBy] ?? 'catalog.id';
        $direction = in_array(mb_strtolower($orderDirection), ['asc', 'desc'], true) ? mb_strtolower($orderDirection) : 'asc';

        if ($sortColumn === 'products.name') {
            $query->join('product_variants', 'catalog.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->select('catalog.*')
                ->orderBy($sortColumn, $direction);
        } else {
            $query->select('catalog.*')
                ->orderBy($sortColumn, $direction);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
