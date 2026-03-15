# Phase 6 — Task 03: Inventory Reports — Testing

## Test File Locations

| Type | Path |
|---|---|
| Feature — controller | `tests/Feature/Reports/InventoryReportControllerTest.php` |
| Unit — stock levels | `tests/Unit/Services/Reports/InventoryReportServiceStockTest.php` |
| Unit — batch status | `tests/Unit/Services/Reports/InventoryReportServiceBatchTest.php` |
| Unit — movement query | `tests/Unit/Queries/StockMovementQueryTest.php` |

## Test Cases

### InventoryReportController (Feature)
- Admin receives all three section props in Inertia response
- Sales Rep with `reports.view_own` receives data scoped to their store only
- User without any report permission receives 403
- Unauthenticated request redirects to login
- `store_id` filter ignored for `view_own` user (forced to their store)
- Partial reload with `only: ['stockLevels']` returns only stock levels

### Stock Levels (Unit)
- Returns paginated list of product variants with current `stock_quantity`
- `low_stock: true` filter returns only variants where `stock_quantity < minimum_stock_level`
- `category_id` filter returns only variants of products in that category
- `brand_id` filter returns only variants of that brand
- Store scoping applied correctly for both Admin and Sales Rep

### Batch Status (Unit)
- Returns batches with correct `expiry_date`, `quantity`, `status`
- `expiry_from` / `expiry_to` filter bounds work inclusively
- `status` filter isolates `active`, `expired`, and `depleted` batches independently
- Store scoping correct

### StockMovementQuery (Unit)
- Result set includes rows from all 4 sources (sale, adjustment, reception, transfer)
- `sale` rows have negative `qty_change` (deduction)
- `reception` rows have positive `qty_change`
- `type` filter returns only matching movement types
- Date range filter applied across all UNION branches
- Variant filter applied across all UNION branches
- Result is orderable by `occurred_at` descending
- Paginator returns correct `total` count across all UNION branches
- Store scoping applied within each UNION branch independently

## Coverage Goals
- `StockMovementQuery`: **100%** — each UNION branch and all filters
- Service methods: **90%+**
- Controller: **80%+** (auth, scoping, validation)

## Notes
- Use `RefreshDatabase` trait in feature tests
- Seed varied movement types in setup to ensure UNION returns cross-source results
- Test `qty_change` sign convention explicitly (sales = negative)
