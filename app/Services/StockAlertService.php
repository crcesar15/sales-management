<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class StockAlertService
{
    /**
     * Get variants where aggregated batch stock < minimum_stock_level.
     *
     * @param  int|array<int, int>|null  $storeIds
     * @return LengthAwarePaginator<int, ProductVariant>
     */
    public function getLowStockAlerts(
        int|array|null $storeIds = null,
        int $perPage = 20,
        string $pageName = 'low_stock_page',
    ): LengthAwarePaginator {
        $storeIds = $this->normalizeStoreIds($storeIds);

        if ($storeIds !== null) {
            return $this->getLowStockAlertsByStores($storeIds, $perPage, $pageName);
        }

        return ProductVariant::query()
            ->select('product_variants.*')
            ->selectRaw('(product_variants.minimum_stock_level - product_variants.stock) as shortage_quantity')
            ->whereNotNull('minimum_stock_level')
            ->whereColumn('stock', '<', 'minimum_stock_level')
            ->with(['product.brand', 'values.option'])
            ->orderByRaw('CASE WHEN product_variants.stock = 0 THEN 0 ELSE 1 END')
            ->orderByDesc('shortage_quantity')
            ->orderBy('product_variants.id')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
    }

    /**
     * Get batches expiring within the configured threshold.
     *
     * @param  int|array<int, int>|null  $storeIds
     * @return LengthAwarePaginator<int, Batch>
     */
    public function getExpiryAlerts(
        int|array|null $storeIds = null,
        int $perPage = 20,
        string $pageName = 'expiry_page',
    ): LengthAwarePaginator {
        $days = $this->getExpiryThresholdDays();
        $storeIds = $this->normalizeStoreIds($storeIds);

        return Batch::query()
            ->expiringSoon($days)
            ->where('remaining_quantity', '>', 0)
            ->when($days === 0 || $storeIds === [], fn ($q) => $q->whereRaw('1 = 0'))
            ->when($storeIds !== null, fn ($q) => $q->whereIn('store_id', $storeIds))
            ->with(['productVariant.product.brand', 'productVariant.values.option', 'store'])
            ->orderBy('expiry_date', 'asc')
            ->orderBy('batch_identifier')
            ->orderBy('id')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
    }

    private function getExpiryThresholdDays(): int
    {
        return (int) Setting::get('expiry_alert_days', 30);
    }

    /**
     * @param  array<int, int>  $storeIds
     * @return LengthAwarePaginator<int, ProductVariant>
     */
    private function getLowStockAlertsByStores(array $storeIds, int $perPage, string $pageName): LengthAwarePaginator
    {
        $stockSubquery = Batch::query()
            ->select('product_variant_id', DB::raw('SUM(remaining_quantity) as store_stock'))
            ->active()
            ->whereIn('store_id', $storeIds)
            ->groupBy('product_variant_id');

        return ProductVariant::query()
            ->select('product_variants.*', DB::raw('CAST(COALESCE(stock_agg.store_stock, 0) AS UNSIGNED) as stock'))
            ->selectRaw('(product_variants.minimum_stock_level - COALESCE(stock_agg.store_stock, 0)) as shortage_quantity')
            ->leftJoinSub($stockSubquery, 'stock_agg', fn ($join) => $join
                ->on('product_variants.id', '=', 'stock_agg.product_variant_id')
            )
            ->whereNotNull('product_variants.minimum_stock_level')
            ->whereHas('batches', fn ($query) => $query->whereIn('store_id', $storeIds))
            ->whereRaw('COALESCE(stock_agg.store_stock, 0) < product_variants.minimum_stock_level')
            ->with(['product.brand', 'values.option'])
            ->orderByRaw('CASE WHEN COALESCE(stock_agg.store_stock, 0) = 0 THEN 0 ELSE 1 END')
            ->orderByDesc('shortage_quantity')
            ->orderBy('product_variants.id')
            ->paginate($perPage, ['*'], $pageName)
            ->withQueryString();
    }

    /**
     * @param  int|array<int, int>|null  $storeIds
     * @return array<int, int>|null
     */
    private function normalizeStoreIds(int|array|null $storeIds): ?array
    {
        if ($storeIds === null || is_array($storeIds)) {
            return $storeIds;
        }

        return [$storeIds];
    }
}
