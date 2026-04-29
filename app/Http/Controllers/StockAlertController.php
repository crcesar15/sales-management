<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Services\StockAlertService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class StockAlertController extends Controller
{
    public function __construct(
        private readonly StockAlertService $stockAlertService,
    ) {}

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::STOCK_ALERTS_VIEW, auth()->user());

        $storeId = request()->integer('store_id') ?: null;

        return Inertia::render('Inventory/Alerts/Index', [
            'lowStockAlerts' => $this->stockAlertService->getLowStockAlerts($storeId),
            'expiryAlerts' => $this->stockAlertService->getExpiryAlerts($storeId),
            'summary' => $this->stockAlertService->getSummary($storeId),
            'filters' => [
                'store_id' => $storeId,
            ],
        ]);
    }

    public function summary(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::STOCK_ALERTS_VIEW, auth()->user());

        $storeId = request()->integer('store_id') ?: null;

        return Inertia::render('Inventory/Alerts/Summary', [
            'summary' => $this->stockAlertService->getSummary($storeId),
        ]);
    }
}
