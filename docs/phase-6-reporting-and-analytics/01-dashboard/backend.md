# Phase 6 — Task 01: Dashboard — Backend

## Key Implementation Steps

1. **Create `DashboardController`** — single `index` action returning Inertia response with initial KPIs, alerts, and deferred chart props
2. **Create `DashboardService`** — extract all query logic out of the controller
3. **Scope by role** — inject authenticated user; check `hasRole('admin')` to decide whether to apply store/user filters
4. **Build KPI methods** in `DashboardService`:
   - `todayRevenue(User, ?int $storeId): float`
   - `monthlyRevenue(User, ?int $storeId, Carbon $from, Carbon $to): float`
   - `lowStockCount(User, ?int $storeId): int`
   - `pendingPoCount(User, ?int $storeId): int`
   - `topProducts(User, ?int $storeId, int $limit = 5): Collection`
5. **Build chart methods**:
   - `revenueTrend(User, ?int $storeId, int $months = 6): array`
   - `topProductsChart(User, ?int $storeId): array`
6. **Active alerts** — query `stock_alerts` where `status = 'active'`, scoped by store
7. **Deferred props** — wrap chart data in `Inertia::defer(fn() => [...])` to avoid blocking initial render
8. **Polling support** — controller `index` action supports Inertia partial reloads (`only` param handled automatically)
9. **Register route** — `Route::get('/dashboard', DashboardController::class)->middleware(['auth'])`
10. **Cache KPIs** — optionally cache per-user/store with a 60-second TTL using `Cache::remember`

## Key Classes / Files

| File | Purpose |
|---|---|
| `app/Http/Controllers/DashboardController.php` | Inertia render, delegates to service |
| `app/Services/DashboardService.php` | All KPI + chart query logic |
| `app/Http/Requests/DashboardFilterRequest.php` | Validates `store_id`, `date_from`, `date_to` |
| `routes/web.php` | Dashboard route definition |

## Important Patterns

```php
// Role-scoped query helper inside DashboardService
private function baseOrderQuery(User $user, ?int $storeId): Builder
{
    $query = SalesOrder::where('status', 'paid');

    if ($user->hasRole('sales_rep')) {
        return $query->where('user_id', $user->id);
    }

    return $storeId ? $query->where('store_id', $storeId) : $query;
}
```

```php
// Deferred chart props in controller
return Inertia::render('Dashboard/Index', [
    'kpis'   => $service->getKpis($user, $request),
    'alerts' => $service->getAlerts($user, $request->store_id),
    'charts' => Inertia::defer(fn() => $service->getCharts($user, $request)),
]);
```

## Gotchas
- `whereDate()` uses the DB server's timezone — ensure `APP_TIMEZONE` matches the store's timezone
- Sales Rep role check must happen server-side; never trust client-passed `user_id` filters
- `SUM()` returns `null` on no rows — cast to `float` with `(float)` or `?? 0.0`
- Deferred props require Inertia v2+ on both server and client
