<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StockAlertService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class HomeController extends Controller
{
    public function __construct(
        private readonly StockAlertService $stockAlertService,
    ) {
        $this->middleware('auth');
    }

    public function index(): InertiaResponse
    {
        $alertSummary = $this->stockAlertService->getSummary();

        return Inertia::render('Dashboard/Index', [
            'alertSummary' => $alertSummary,
        ]);
    }
}
