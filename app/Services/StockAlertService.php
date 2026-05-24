<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class StockAlertService
{
    /**
     * Get variants where aggregated batch stock < minimum_stock_level.
     *
     * @return Collection<int, ProductVariant>
     */
    public function getLowStockAlerts(?int $storeId = null): Collection
    {
        if ($storeId !== null) {
            return $this->getLowStockAlertsByStore($storeId);
        }

        return ProductVariant::query()
            ->whereNotNull('minimum_stock_level')
            ->whereColumn('stock', '<', 'minimum_stock_level')
            ->with(['product.brand', 'values.option'])
            ->get();
    }

    /**
     * Get batches expiring within the configured threshold.
     *
     * @return Collection<int, Batch>
     */
    public function getExpiryAlerts(?int $storeId = null): Collection
    {
        $days = $this->getExpiryThresholdDays();

        if ($days === 0) {
            return new Collection;
        }

        return Batch::query()
            ->expiringSoon($days)
            ->where('remaining_quantity', '>', 0)
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->with(['productVariant.product', 'store'])
            ->orderBy('expiry_date', 'asc')
            ->get();
    }

    /**
     * Get summary counts for both alert types.
     *
     * @return array{low_stock_count: int, expiry_count: int, total: int}
     */
    public function getSummary(?int $storeId = null): array
    {
        $lowStockCount = $this->countLowStock($storeId);
        $expiryCount = $this->countExpiry($storeId);

        return [
            'low_stock_count' => $lowStockCount,
            'expiry_count' => $expiryCount,
            'total' => $lowStockCount + $expiryCount,
        ];
    }

    private function getExpiryThresholdDays(): int
    {
        return (int) Setting::get('expiry_alert_days', 30);
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    private function getLowStockAlertsByStore(int $storeId): Collection
    {
        $stockSubquery = Batch::query()
            ->select('product_variant_id', DB::raw('SUM(remaining_quantity) as store_stock'))
            ->active()
            ->where('store_id', $storeId)
            ->groupBy('product_variant_id');

        return ProductVariant::query()
            ->select('product_variants.*', DB::raw('CAST(COALESCE(stock_agg.store_stock, 0) AS UNSIGNED) as stock'))
            ->joinSub($stockSubquery, 'stock_agg', fn ($join) => $join
                ->on('product_variants.id', '=', 'stock_agg.product_variant_id')
            )
            ->whereNotNull('product_variants.minimum_stock_level')
            ->whereColumn('stock_agg.store_stock', '<', 'product_variants.minimum_stock_level')
            ->with(['product.brand', 'values.option'])
            ->get();
    }

    private function countLowStock(?int $storeId): int
    {
        if ($storeId !== null) {
            $stockSubquery = Batch::query()
                ->select('product_variant_id', DB::raw('SUM(remaining_quantity) as store_stock'))
                ->active()
                ->where('store_id', $storeId)
                ->groupBy('product_variant_id');

            return ProductVariant::query()
                ->joinSub($stockSubquery, 'stock_agg', fn ($join) => $join
                    ->on('product_variants.id', '=', 'stock_agg.product_variant_id')
                )
                ->whereNotNull('product_variants.minimum_stock_level')
                ->whereColumn('stock_agg.store_stock', '<', 'product_variants.minimum_stock_level')
                ->count();
        }

        return ProductVariant::query()
            ->whereNotNull('minimum_stock_level')
            ->whereColumn('stock', '<', 'minimum_stock_level')
            ->count();
    }

    private function countExpiry(?int $storeId): int
    {
        $days = $this->getExpiryThresholdDays();

        if ($days === 0) {
            return 0;
        }

        return Batch::query()
            ->expiringSoon($days)
            ->where('remaining_quantity', '>', 0)
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->count();
    }
}
