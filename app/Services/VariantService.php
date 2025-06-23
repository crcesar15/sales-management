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
        $selectArray = ['product_variants.*'];

        // Includes
        foreach ($config['includes'] as $include) {
            switch ($include) {
                case 'vendors':
                    $query->with('vendors');
                    break;
                case 'product':
                    $query->join('products', 'product_variants.product_id', '=', 'products.id')
                        ->join('brands', 'products.brand_id', '=', 'brands.id')
                        ->join('measure_units', 'products.measure_unit_id', '=', 'measure_units.id')
                        ->leftJoin('category_product', 'products.id', '=', 'category_product.product_id')
                        ->leftJoin('categories', 'category_product.category_id', '=', 'categories.id')
                        ->groupBy('product_variants.id');

                    $selectArray[] = 'products.name as product_name';
                    $selectArray[] = 'brands.name as brand_name';
                    $selectArray[] = 'measure_units.name as measure_unit_name';
                    $selectArray[] = DB::raw('GROUP_CONCAT(DISTINCT categories.name ORDER BY categories.name SEPARATOR ", ") as categories');
                    break;
            }
        }

        $query->select($selectArray);

        // Filter by status
        if ($config['status'] !== 'all') {
            $query->where('product_variants.status', $config['status']);
        }

        // Filter by name or other fields
        // if (!empty($config['filter'])) {
        //     $filter = '%' . $config['filter'] . '%';
        //     $filterBy = $config['filter_by'] === 'name' ? 'products.name' : $config['filter_by'];
        //     $query->where($filterBy, 'like', $filter);
        // }

        // Filter by vendor id
        if (isset($config['vendor_id']) && !empty($config['vendor_id'])) {
            $query->where('vendors.id', $config['vendor_id']);
        }

        // Order by
        $orderBy = $config['order_by'] === 'name' ? 'products.name' : $config['order_by'];
        $orderDirection = $config['order_direction'] ?? 'ASC';
        $query->orderBy($orderBy, $orderDirection);

        //pagination
        $page = $config['page'];
        $perPage = $config['per_page'];

        $query->skip(($page - 1) * $perPage)->take($perPage);

        return $query->get();
    }
}
