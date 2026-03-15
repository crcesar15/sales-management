# Phase 6 — Task 04: Purchasing Reports — Testing

## Test File Locations

| Type | Path |
|---|---|
| Feature — controller | `tests/Feature/Reports/PurchasingReportControllerTest.php` |
| Unit — PO summary | `tests/Unit/Services/Reports/PurchasingReportServicePoTest.php` |
| Unit — vendor spend | `tests/Unit/Services/Reports/PurchasingReportServiceVendorTest.php` |
| Unit — reception accuracy | `tests/Unit/Services/Reports/PurchasingReportServiceAccuracyTest.php` |

## Test Cases

### PurchasingReportController (Feature)
- Admin user receives all three section props in Inertia response
- Sales Rep user receives 403
- User with `reports.view_own` (non-admin) receives 403
- Unauthenticated user redirects to login
- Invalid `date_from` format returns validation error
- `date_from` > `date_to` returns validation error
- Partial reload `only: ['poSummary']` returns only that section

### PO Summary — Service (Unit)
- `total_spend` sums only `received` and `partially_received` POs
- `total_spend` excludes `pending` and `cancelled` POs
- `pending_count` counts only `pending` status POs
- `avg_lead_time_days` returns `null` when no received POs exist
- `avg_lead_time_days` correctly averages DATEDIFF for multiple received POs
- `vendor_id` filter narrows all metrics to that vendor
- Date range filter applied correctly

### Vendor Spend — Service (Unit)
- Returns one row per vendor with correct `order_count` and `total_ordered_value`
- Rows ordered by `total_ordered_value` descending
- Date range filter applies to `purchase_orders.created_at`
- Vendor with zero orders in range does not appear in results
- Vendor name populated correctly via relationship

### Reception Accuracy — Service (Unit)
- Returns line items comparing `quantity_ordered` vs `quantity_received`
- `variance` = `qty_received - qty_ordered` (negative = under)
- `variance_pct` correct to 2 decimal places
- Multiple receptions for same PO item are aggregated into one row (`SUM`)
- `variance_type = 'over'` returns only rows where `variance > 0`
- `variance_type = 'under'` returns only rows where `variance < 0`
- `variance_type = 'exact'` returns only rows where `variance = 0`
- Vendor filter narrows results
- `quantity_ordered = 0` does not cause division by zero error

## Coverage Goals
- All service methods: **100%** branch coverage (edge cases: zero values, null lead time, multi-reception aggregation)
- Controller: **80%+** (auth, permission 403, validation)
