# Phase 6 — Task 01: Dashboard — Testing

## Test File Locations

| Type | Path |
|---|---|
| Feature — controller + scoping | `tests/Feature/Dashboard/DashboardControllerTest.php` |
| Unit — service KPI calculations | `tests/Unit/Services/DashboardServiceTest.php` |
| Unit — chart data transformation | `tests/Unit/Services/DashboardServiceChartTest.php` |

## Test Cases

### DashboardController (Feature)
- Admin user receives `kpis`, `alerts`, `charts` props in Inertia response
- Sales Rep receives response scoped to their `user_id` (not full store)
- Unauthenticated request redirects to login
- `store_id` filter accepted for Admin; ignored/overridden for Sales Rep
- Invalid `date_from` > `date_to` returns validation error
- Partial reload (`only=kpis`) returns only `kpis` prop (Inertia partial)

### DashboardService — KPI (Unit)
- `todayRevenue` sums only `status = paid` orders created today
- `todayRevenue` excludes draft / cancelled orders
- `monthlyRevenue` returns 0.0 (not null) when no orders exist
- `lowStockCount` counts only variants where `stock_quantity < minimum_stock_level`
- `pendingPoCount` counts only POs with `status = pending`
- `topProducts` returns max 5 results ordered by quantity descending
- All methods respect `store_id` scope when passed
- Sales Rep scope applied via `user_id` filter, not `store_id`

### DashboardService — Charts (Unit)
- `revenueTrend` returns exactly 6 entries (fills 0 for months with no sales)
- `revenueTrend` entries are ordered chronologically oldest → newest
- `topProductsChart` labels match product names, not variant SKUs
- No future months appear in trend data

### DashboardService — Alerts (Unit)
- Returns only `status = active` alerts
- Scoped to correct store for Sales Rep
- Returns empty array (not null) when no alerts

## Coverage Goals
- Service methods: **100%** branch coverage
- Controller: **80%+** (happy path + auth scoping)
- Focus on role-scoping correctness over UI behaviour

## Factories / Seeders Needed
- `SalesOrderFactory` with `status`, `store_id`, `user_id`, `created_at` overrides
- `StockAlertFactory` with `type` and `status` overrides
- `ProductVariantFactory` with `stock_quantity` and `minimum_stock_level`
