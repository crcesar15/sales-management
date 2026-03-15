# Task 05 — Backend: Stock Alerts

## Key Implementation Steps
1. Create migration `add_minimum_stock_level_to_product_variants_table`
2. Update `ProductVariant` model: add `minimum_stock_level` to `$fillable`
3. Create `StockAlertService` — two query methods: low-stock and expiry
4. Create `StockAlertController` with `index` and `summary` actions
5. Apply store-scoping based on user role in controller
6. Inject `StockAlertService` into dashboard controller for summary widget
7. Add routes under `/inventory/alerts`

## Key Classes / Files
| File | Purpose |
|---|---|
| `database/migrations/..._add_minimum_stock_level_to_product_variants_table.php` | New column |
| `app/Models/ProductVariant.php` | Add `minimum_stock_level` to `$fillable` |
| `app/Services/Inventory/StockAlertService.php` | Alert computation |
| `app/Http/Controllers/Inventory/StockAlertController.php` | index + summary |
| `app/Http/Controllers/DashboardController.php` | Inject summary for dashboard widget |

## StockAlertService — Key Methods
```php
// Returns array of low-stock variant data, scoped to store if provided
public function getLowStockAlerts(?int $storeId = null): Collection

// Returns array of expiry-approaching batch data
public function getExpiryAlerts(?int $storeId = null): Collection

// Returns ['low_stock_count' => n, 'expiry_count' => n, 'total' => n]
public function getSummary(?int $storeId = null): array

// Reads from settings table (cached)
private function getExpiryThresholdDays(): int
```

## Store Scoping Pattern
```php
// In controller
$storeId = auth()->user()->hasRole('admin')
    ? $request->input('store_id')    // Admin can filter by any store
    : auth()->user()->store_id;       // Sales Rep always scoped to own store
```

## Settings Integration
- Read `expiry_alert_days` from settings via a `SettingsService` or `config` helper
- Cache the value for the duration of the request (no repeated DB hits)

## Packages
- `spatie/laravel-permission` — role check for store scoping

## Performance Considerations
- Both queries use aggregation — run them with DB indexes in mind (Task 01 indexes apply)
- Summary endpoint: run both queries, count results — do not load full collections
- For v1, acceptable performance target: < 500ms for catalogs up to ~5000 variants

## Gotchas
- Variants with `minimum_stock_level = null` must never appear in low-stock alerts
- Batches with `expiry_date = null` must never appear in expiry alerts
- `expiry_alert_days = 0` disables expiry alerts — return empty array, not all batches
- An expired batch (past today) should still appear in alerts until it's closed
