# Phase 6 — Task 04: Purchasing Reports — Overview

## What
Three-section purchasing report providing a summary of purchase orders, vendor spend analysis, and reception accuracy tracking (over/under delivery). Admin only. No new tables — aggregated from existing purchasing data.

## Why
Enables procurement managers to monitor spending, evaluate vendor reliability, and identify systematic delivery discrepancies before they cause stock imbalances.

## Requirements

### Section 1 — Purchase Order Summary
- List of POs with filters: vendor, status, date range
- Metrics: total spend (sum of received POs), pending orders count, average lead time
- Lead time = days between PO `created_at` and `received_at` (or first reception date)

### Section 2 — Vendor Spend
- Breakdown of total spend per vendor over a selected period
- Sortable by total spend descending
- Shows: vendor name, number of orders, total ordered value, total received value

### Section 3 — Reception Accuracy
- For completed receptions: compare `quantity_ordered` vs `quantity_received` per line item
- Columns: PO ref, vendor, product, qty ordered, qty received, variance, variance %
- Filterable by vendor, date range, variance type (over / under / exact)

## Acceptance Criteria
- [ ] PO Summary shows correct total spend for completed/received POs only
- [ ] Average lead time is calculated only for POs that have been received
- [ ] Vendor Spend section aggregates correctly per vendor within selected date range
- [ ] Reception Accuracy highlights over and under deliveries distinctly
- [ ] All sections restricted to Admin (`reports.view_all` permission)
- [ ] Attempting access as Sales Rep returns 403
- [ ] Each section is independently paginated
- [ ] Date range filter defaults to current month

## Dependencies
- Phase 5: `purchase_orders`, `purchase_order_items`, `vendors`, `reception_orders`, `reception_order_product`
- Spatie Permission: `reports.view_all` (Admin only)

## Notes
- No export in v1
- Lead time may be `null` for POs not yet received — exclude from average calculation
- Reception accuracy only for POs with `status = 'received'` or `status = 'partially_received'`
- Over delivery = `qty_received > qty_ordered`; Under = `qty_received < qty_ordered`
