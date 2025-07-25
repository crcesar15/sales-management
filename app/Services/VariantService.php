<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class VariantService
{
    /**
     * Get all variants with optional filters and includes.
     *
     * @param array $filters
     * @param array $includes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVariants(array $config)
    {
        $query = ProductVariant::query();
        $query->select(['product_variants.*']);

        // Includes
        foreach ($config['includes'] as $include) {
            switch ($include) {
                case 'vendors':
                    $query->with('vendors');
                    // Filter by vendor id
                    if (isset($config['vendor_id']) && !empty($config['vendor_id'])) {
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
        if (!empty($config['filter'])) {
            $filter = '%' . $config['filter'] . '%';
            $filterBy = $config['filter_by'] === 'name' ? 'products.name' : $config['filter_by'];
            $query->where($filterBy, 'like', $filter);
        }

        // Order by
        $orderBy = $config['order_by'] === 'name' ? 'products.name' : $config['order_by'];
        $orderDirection = $config['order_direction'] ?? 'ASC';
        $query->orderBy($orderBy, $orderDirection);

        //pagination
        $page = $config['page'];
        $perPage = $config['per_page'];

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
