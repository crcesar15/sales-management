<?php

declare(strict_types=1);

namespace App\Services;

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

        // Includes
        foreach ($config['includes'] as $include) {
            switch ($include) {
                case 'vendors':
                    $query->with('vendors');
                    // Filter by vendor id
                    if (isset($config['vendor_id']) && ! empty($config['vendor_id'])) {
                        $query->join('catalog', 'product_variants.id', '=', 'catalog.product_variant_id');
                        $query->join('vendors', 'catalog.vendor_id', '=', 'vendors.id');
                        $query->where('vendors.id', $config['vendor_id']);
                    }

                    break;
                case 'product':
                    $query->join('products', 'product_variants.product_id', '=', 'products.id');
                    $query->with('product.categories', 'product.brand');
                    break;
            }
        }

        // Filter by status
        if ($config['status'] !== 'all') {
            $query->where('products.status', $config['status']);
        }

        // Filter by name or other fields
        if (! empty($config['filter'])) {
            $filter = '%' . $config['filter'] . '%';
            $filterBy = $config['filter_by'] === 'name' ? 'products.name' : $config['filter_by'];
            $query->where($filterBy, 'like', $filter);
        }

        // Order by
        $orderBy = $config['order_by'] === 'name' ? 'products.name' : $config['order_by'];
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
            ->with(['product.brand', 'product.categories', 'values.option', 'images'])
            ->when($status !== 'all', fn ($q) => $q->where('product_variants.status', $status))
            ->when($filter, fn ($q) => $q->whereHas(
                'product',
                fn ($pq) => $pq->where('name', 'like', "%{$filter}%")
            ))
            ->orderBy($sortColumn, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }
}
