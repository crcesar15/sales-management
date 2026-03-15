# Phase 6 — Task 02: Sales Reports — Overview

## What
On-screen sales summary report with rich filtering. Shows aggregate KPI metrics and a paginated breakdown table of individual orders. Scoped by permission: users with `reports.view_own` see only their sales; `reports.view_all` see everything.

## Why
Enables managers and sales reps to analyse sales performance, identify trends, review refund activity, and verify totals across any combination of store, product, rep, date range, payment method, and status.

## Requirements
- Filters: store, sales rep (user), product/variant, date range, payment method, status
- Summary metrics:
  - Total revenue
  - Number of orders
  - Average order value
  - Total discount given
  - Total tax collected
  - Total refunds
- Breakdown table columns: order ID, date, customer, cashier, items count, total, status
- Refunds shown as negative entries in the same table OR in a separate collapsible section
- Permission: `reports.view_own` (own sales only) vs `reports.view_all` (all data)
- No CSV/PDF export in v1 — on-screen only
- Paginated results (25 per page default)

## Acceptance Criteria
- [ ] All 6 filter controls render and function independently and in combination
- [ ] Summary metrics update when filters change
- [ ] Breakdown table is paginated and preserves filter state on page change
- [ ] `reports.view_own` user cannot see other reps' orders via URL or filter manipulation
- [ ] Refund rows are visually distinguishable (negative total, different status badge)
- [ ] Empty state shown when no orders match filters
- [ ] All metric totals reconcile with the sum of breakdown table rows

## Dependencies
- Phase 4: `sales_orders`, `sales_order_items`, `customers`, `users`
- Phase 5: `products`, `product_variants`
- Spatie Permission: `reports.view_own`, `reports.view_all`
- PrimeVue: DataTable, MultiSelect, DatePicker

## Notes
- No new tables needed
- Average order value = `total_revenue / order_count` (handle division by zero)
- "Refunds" sourced from `sales_orders` where `status = 'refunded'` or from a `refunds` table if Phase 4 implemented one
- Filter state persisted in URL query params for shareability
