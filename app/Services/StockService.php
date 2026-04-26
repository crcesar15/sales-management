<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class StockService
{
    private const LOW_STOCK_THRESHOLD = 0;

    private const SORT_COLUMN_MAP = [
        'product_name' => 'products.name',
        'brand_name' => 'brands.name',
        'total_stock' => 'global_stock',
        'identifier' => 'product_variants.identifier',
        'price' => 'product_variants.price',
    ];

    /**
     * @return LengthAwarePaginator<int, ProductVariant>
     */
    public function listStockOverview(
        ?int $storeId = null,
        ?int $categoryId = null,
        ?int $brandId = null,
        bool $lowStockOnly = false,
        string $search = '',
        string $orderBy = 'product_name',
        string $orderDirection = 'asc',
        int $perPage = 15,
    ): LengthAwarePaginator {
        $globalStockSubquery = Batch::query()
            ->select('product_variant_id', DB::raw('SUM(remaining_quantity) as global_stock'))
            ->activeOrQueued()
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->groupBy('product_variant_id');

        $sortColumn = self::SORT_COLUMN_MAP[$orderBy] ?? 'products.name';
        $needsProductJoin = in_array($orderBy, ['product_name', 'brand_name', 'price', 'identifier']);
        $needsBrandJoin = $orderBy === 'brand_name';

        return ProductVariant::query()
            ->select('product_variants.*', DB::raw('COALESCE(stock_agg.global_stock, 0) as global_stock'))
            ->when($needsProductJoin, fn ($q) => $q->join(
                'products', 'product_variants.product_id', '=', 'products.id'
            ))
            ->when($needsBrandJoin, fn ($q) => $q->leftJoin(
                'brands', 'products.brand_id', '=', 'brands.id'
            ))
            ->leftJoinSub($globalStockSubquery, 'stock_agg', fn ($join) => $join
                ->on('product_variants.id', '=', 'stock_agg.product_variant_id')
            )
            ->with(['product.brand', 'product.categories', 'values.option', 'images'])
            ->when($storeId, fn ($q) => $q->whereHas(
                'batches', fn (Builder $bq) => $bq->whereIn('status', ['active', 'queued'])->where('store_id', $storeId)
            ))
            ->when($categoryId, fn ($q) => $q->whereHas(
                'product.categories', fn ($cq) => $cq->where('categories.id', $categoryId)
            ))
            ->when($brandId, fn ($q) => $q->whereHas(
                'product', fn ($pq) => $pq->where('brand_id', $brandId)
            ))
            ->when($lowStockOnly, fn ($q) => $q->whereRaw(
                'COALESCE(stock_agg.global_stock, 0) <= ?', [self::LOW_STOCK_THRESHOLD]
            ))
            ->when($search, fn ($q) => $q->whereHas(
                'product', fn ($pq) => $pq->where('name', 'like', "%{$search}%")
                    ->orWhere('products.name', 'like', "%{$search}%")
            ))
            ->orderBy($sortColumn, $orderDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array{variant: ProductVariant, stores: \Illuminate\Support\Collection<int, array{store: \App\Models\Store|null, quantity: int}>, total_quantity: int}
     */
    public function getVariantStockBreakdown(ProductVariant $variant): array
    {
        $variant->load(['product.brand', 'product.categories', 'values.option']);

        $perStoreStock = Batch::query()
            ->select('store_id', DB::raw('SUM(remaining_quantity) as quantity'))
            ->where('product_variant_id', $variant->id)
            ->activeOrQueued()
            ->groupBy('store_id')
            ->with('store')
            ->get();

        $stores = $perStoreStock->map(fn (Batch $row) => [
            'store' => $row->store,
            'quantity' => (int) $row->getAttribute('quantity'),
        ]);

        return [
            'variant' => $variant,
            'stores' => $stores,
            'total_quantity' => (int) $perStoreStock->sum('quantity'),
        ];
    }
}
