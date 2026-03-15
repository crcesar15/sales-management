# Task 05 — Stock Alerts

## What
Query-based alert system that surfaces two alert types — low stock and expiry approaching —
on the dashboard and stock overview page. No background jobs; alerts are computed on demand.

## Why
Operators need proactive visibility into critical inventory states without manually scanning
every product. Alerts aggregate these signals into a single, actionable summary.

## Requirements
- [ ] **Low stock alert**: variant stock in any store < `minimum_stock_level`
- [ ] **Expiry alert**: batch `expiry_date` within `expiry_alert_days` (from settings)
- [ ] Add `minimum_stock_level` column to `product_variants` (new migration)
- [ ] Alerts endpoint returns current active alert summary
- [ ] Alerts surfaced on dashboard (summary count) and stock overview (inline badges)
- [ ] Admin sees all alerts; Sales Rep sees alerts for their store only
- [ ] No push notifications, no background queue — query-based for v1

## Acceptance Criteria
- [ ] Low-stock alerts appear only for variants with a non-null `minimum_stock_level`
- [ ] Expiry alerts appear only for batches with a non-null `expiry_date`
- [ ] Threshold (`expiry_alert_days`) is read from `settings` table
- [ ] Sales Rep alert scope is filtered to their assigned store
- [ ] Alert counts on dashboard match the detailed alert list

## Dependencies
| Dependency | Notes |
|---|---|
| `product_variants.minimum_stock_level` | New column (this task) |
| `batches.expiry_date` | Existing nullable column |
| `settings` table | `expiry_alert_days` value |
| `batches.store_id` | From Task 01 migration |
| `spatie/laravel-permission` | Role-based scope filtering |

## New Migration
- `add_minimum_stock_level_to_product_variants_table`
  → adds `minimum_stock_level` int nullable

## Cross-Phase Notes
- `minimum_stock_level` is consumed by Task 01 (low-stock badge) and Task 05 (alerts)
- `expiry_date` was set during reception (Phase 4); alerts are computed here
- Stock totals for low-stock check use same aggregation as Task 01

## Notes
- For v1: no `alerts` table — alerts are always freshly computed
- Future v2: consider a scheduled job to cache alert states for large catalogs
- `expiry_alert_days = 0` means expiry alerts are disabled
