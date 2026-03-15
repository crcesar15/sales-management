# Phase 6 — Task 02: Sales Reports — Testing

## Test File Locations

| Type | Path |
|---|---|
| Feature — controller | `tests/Feature/Reports/SalesReportControllerTest.php` |
| Unit — service | `tests/Unit/Services/Reports/SalesReportServiceTest.php` |
| Unit — filter DTO | `tests/Unit/DTOs/SalesReportFilterDTOTest.php` |

## Test Cases

### SalesReportController (Feature)
- User with `reports.view_own` receives only their own orders in response
- User with `reports.view_all` receives all orders by default
- `view_own` user cannot retrieve other users' orders by passing `user_id` param
- `view_all` user can filter by `user_id`
- `view_all` user can filter by `store_id`
- Unauthenticated request redirects to login
- User with no report permission receives 403
- Invalid `date_from` (non-date string) returns validation error
- `date_from` > `date_to` returns validation error
- Correct `metrics` values returned for filtered result set
- Pagination works: page 2 returns different records, total matches metrics count

### SalesReportService — Metrics (Unit)
- `total_revenue` sums `total` of all matching orders
- `total_discount` sums `discount_total` correctly
- `total_tax` sums `tax_total` correctly
- `total_refunds` only sums orders with `status = refunded`
- `avg_order_value` returns 0 when order count is 0 (no division by zero)
- `order_count` counts distinct orders, not items
- Filters (`status`, `payment_method`, `date range`) each independently narrow results

### SalesReportService — Orders (Unit)
- Returns paginated `LengthAwarePaginator`
- Each result includes `customer.name`, `user.name`, `items_count`
- `items_count` reflects correct number of line items per order
- Refunded orders appear in the result set with negative-signalling status

### SalesReportFilterRequest (Unit)
- Missing `date_from` defaults to start of current month
- Missing `date_to` defaults to today
- `view_own` user's `user_id` is forced to `auth()->id()` regardless of input

## Coverage Goals
- Service: **100%** — all filter combinations, edge cases (empty result, zero values)
- Controller: **80%+** — auth, permission scoping, validation
- DTO: basic construction and immutability
