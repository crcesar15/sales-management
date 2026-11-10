<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Dashboard\IndexDashboardRequest;
use App\Http\Resources\Dashboard\DashboardResource;
use App\Services\DashboardService;
use App\Services\ReportingScopeService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class HomeController extends Controller
{
    public function __construct(private readonly DashboardService $dashboard, private readonly ReportingScopeService $scope) {}

    public function index(IndexDashboardRequest $request): InertiaResponse
    {
        $user = $request->user() ?? abort(401);

        return Inertia::render('Dashboard/Index', [
            'dashboard' => fn () => (new DashboardResource($this->dashboard->snapshot($user, $request->validated())))->resolve($request),
            'stores' => fn () => $this->scope->stores($user)->map(fn ($store): array => [
                'id' => $store->id, 'name' => $store->name, 'code' => $store->code,
                'archived' => $store->trashed(), 'status' => $store->status,
            ])->values(),
        ]);
    }
}
