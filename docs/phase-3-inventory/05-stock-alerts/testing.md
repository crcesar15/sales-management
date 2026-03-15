# Task 05 — Testing: Stock Alerts

## Test File Locations
| File | Type |
|---|---|
| `tests/Feature/Inventory/StockAlertTest.php` | Feature (HTTP) |
| `tests/Unit/Services/Inventory/StockAlertServiceTest.php` | Unit |

## Feature Test Cases

**Access control**
- Admin can access `/inventory/alerts` and see all stores' alerts
- Sales Rep can access `/inventory/alerts` and sees only their store's alerts
- Sales Rep cannot pass `store_id` param to see other stores (scoped in controller)
- Guest redirected to login

**Low-stock alerts**
- Variant with `minimum_stock_level = null` does NOT appear in alerts
- Variant where stock < `minimum_stock_level` appears in low-stock list
- Variant where stock >= `minimum_stock_level` does NOT appear
- Stock is aggregated correctly across active + queued batches

**Expiry alerts**
- Batch with `expiry_date = null` does NOT appear in expiry alerts
- Batch with `expiry_date` within threshold appears in alerts
- Batch with `expiry_date` beyond threshold does NOT appear
- Batch with `status = closed` does NOT appear even if expiring
- `expiry_alert_days = 0` in settings: returns empty expiry list

**Summary endpoint**
- Returns correct `low_stock_count` and `expiry_count`
- Returns `total` = sum of both counts
- Responds even when no alerts exist (all zeros)

**Store scoping**
- Admin with `store_id` param sees only that store's alerts
- Sales Rep auto-scoped to their store — other stores not included

## Unit Test Cases — `StockAlertService`
- `getLowStockAlerts()` excludes null-minimum variants
- `getLowStockAlerts()` applies store filter correctly
- `getExpiryAlerts()` excludes null-expiry batches
- `getExpiryAlerts()` respects `expiry_alert_days` threshold
- `getExpiryAlerts()` excludes closed batches
- `getSummary()` returns correct counts matching individual query results
- `getExpiryThresholdDays()` returns 0 when not configured (no crash)

## Coverage Goals
- [ ] Null guard for `minimum_stock_level` and `expiry_date`
- [ ] Threshold boundary tested (exactly at threshold, one day over, one day under)
- [ ] Role-based scoping verified in both feature and unit tests
- [ ] `expiry_alert_days = 0` edge case covered
- [ ] Dashboard summary counts match detailed list counts
