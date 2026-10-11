<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Http\Resources\Inventory\ExpiryAlertCollection;
use App\Http\Resources\Inventory\LowStockAlertCollection;
use App\Models\Store;
use App\Models\User;
use App\Services\StockAlertService;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class StockAlertController extends Controller
{
    public function __construct(
        private readonly StockAlertService $stockAlertService,
    ) {}

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::STOCK_ALERTS_VIEW);

        /** @var User $user */
        $user = auth()->user();
        [$stores, $storeIds, $storeId] = $this->resolveStoreScope($user);
        $lowStockAlerts = $this->stockAlertService->getLowStockAlerts(
            $storeIds,
            $this->boundedPerPage(request()->integer('low_stock_per_page', 20)),
        );
        $expiryAlerts = $this->stockAlertService->getExpiryAlerts(
            $storeIds,
            $this->boundedPerPage(request()->integer('expiry_per_page', 20)),
        );
        $lowStockCount = $lowStockAlerts->total();
        $expiryCount = $expiryAlerts->total();
        $activeTab = request()->string('tab', 'low-stock')->value();

        return Inertia::render('Inventory/Alerts/Index', [
            'lowStockAlerts' => new LowStockAlertCollection($lowStockAlerts),
            'expiryAlerts' => new ExpiryAlertCollection($expiryAlerts),
            'summary' => [
                'low_stock_count' => $lowStockCount,
                'expiry_count' => $expiryCount,
                'total' => $lowStockCount + $expiryCount,
            ],
            'stores' => $stores,
            'filters' => [
                'store_id' => $storeId,
                'tab' => in_array($activeTab, ['low-stock', 'expiry'], true) ? $activeTab : 'low-stock',
            ],
        ]);
    }

    /**
     * @return array{0: Collection<int, Store>, 1: array<int, int>|null, 2: int|null}
     */
    private function resolveStoreScope(User $user): array
    {
        $isAdministrator = $user->hasRole(RolesEnum::ADMIN->value);
        $storesQuery = Store::query()->where('status', 'active');

        if (! $isAdministrator) {
            $storesQuery->whereIn('id', $user->stores()->select('stores.id'));
        }

        /** @var Collection<int, Store> $stores */
        $stores = $storesQuery->orderBy('name')->get(['id', 'name', 'code']);
        $storeId = request()->integer('store_id') ?: null;

        if ($storeId !== null && ! $stores->contains('id', $storeId)) {
            abort($isAdministrator ? 404 : 403);
        }

        return [$stores, $storeId === null ? $stores->pluck('id')->all() : [$storeId], $storeId];
    }

    private function boundedPerPage(int $perPage): int
    {
        return min(max($perPage, 1), 100);
    }
}
