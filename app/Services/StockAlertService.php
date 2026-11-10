<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class StockAlertService
{
    /**
     * One row per historically stocked variant/store, including depleted batches.
     * Saleable quantity follows fulfillment's expiry rules.
     *
     * @param  list<int>  $storeIds
     */
    public function storePositions(array $storeIds, ?string $businessDate = null): Builder
    {
        $today = $businessDate ?? now((string) Setting::get('timezone', config('app.timezone')))->toDateString();
        $stock = DB::table('batches')->whereIn('store_id', $storeIds)
            ->select('product_variant_id', 'store_id')
            ->selectRaw("SUM(CASE WHEN status = 'active' AND (expiry_date IS NULL OR expiry_date >= ?) THEN remaining_quantity ELSE 0 END) as stock", [$today])
            ->groupBy('product_variant_id', 'store_id');

        return DB::query()->fromSub($stock, 'position')
            ->join('product_variants as variant', 'variant.id', '=', 'position.product_variant_id')
            ->join('products as product', 'product.id', '=', 'variant.product_id')
            ->join('stores as store', 'store.id', '=', 'position.store_id')
            ->where('variant.status', 'active')->where('product.status', 'active')
            ->whereNull('product.deleted_at')->whereNull('store.deleted_at')->where('store.status', 'active')
            ->select('variant.id', 'variant.product_id', 'variant.identifier', 'variant.barcode', 'variant.price', 'variant.status', 'variant.minimum_stock_level', 'position.stock',
                'position.store_id', 'store.name as store_name', 'product.name as product_name');
    }

    /** @param list<int> $storeIds
     * @return LengthAwarePaginator<int, ProductVariant>
     */
    public function getStoreLowStockAlerts(array $storeIds, int $perPage = 20): LengthAwarePaginator
    {
        return ProductVariant::query()->fromSub($this->storePositions($storeIds), 'product_variants')
            ->select('product_variants.*')
            ->selectRaw('CASE WHEN minimum_stock_level IS NULL THEN 0 ELSE minimum_stock_level - stock END as shortage_quantity')
            ->where(fn ($q) => $q->where('stock', '<=', 0)->orWhereColumn('stock', '<', 'minimum_stock_level'))
            ->with(['product.brand', 'values.option'])->orderBy('stock')->orderBy('id')->orderBy('store_id')
            ->paginate($perPage, ['*'], 'low_stock_page')->withQueryString();
    }

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
        bool $includeExpiredWhenDisabled = false,
    ): LengthAwarePaginator {
        $days = $this->getExpiryThresholdDays();
        $storeIds = $this->normalizeStoreIds($storeIds);

        return Batch::query()
            ->expiringSoon($days)
            ->where('remaining_quantity', '>', 0)
            ->when(($days === 0 && ! $includeExpiredWhenDisabled) || $storeIds === [], fn ($q) => $q->whereRaw('1 = 0'))
            ->when($days === 0 && $includeExpiredWhenDisabled, fn ($q) => $q->whereDate('expiry_date', '<', now((string) Setting::get('timezone', config('app.timezone')))->toDateString()))
            ->when($includeExpiredWhenDisabled, fn ($q) => $q->whereHas('store', fn ($stores) => $stores->where('status', 'active')))
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
