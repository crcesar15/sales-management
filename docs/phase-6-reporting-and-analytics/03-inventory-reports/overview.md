# Phase 6 — Task 03: Inventory Reports — Overview

## What
Three-section inventory report page providing a unified view of current stock levels, batch expiry status, and a historical stock movement log. All sections are filterable. No new tables — data aggregated from existing tables.

## Why
Gives Admins (and limited Sales Reps) visibility into stock health, upcoming expiry risks, and a complete audit trail of every stock change across sales, adjustments, transfers, and purchase receptions.

## Requirements

### Section 1 — Stock Levels
- Current `stock_quantity` per variant per store
- Filters: store, category, brand, low-stock flag (variants below `minimum_stock_level`)

### Section 2 — Batch Status
- List of batches with expiry dates, remaining quantity, status
- Filters: store, expiry date range, status (`active`, `expired`, `depleted`)

### Section 3 — Stock Movement History
- Unified log of all stock changes with movement type
- Sources: `sales_order_items` (sale), `stock_adjustments` (adjustment), `stock_transfer_items` (transfer_out / transfer_in), `reception_order_product` (reception)
- Filters: variant, store, date range, movement type

## Acceptance Criteria
- [ ] Stock levels table shows correct current quantity per variant per store
- [ ] Low-stock filter isolates only variants below their minimum level
- [ ] Batch section shows expiry date, remaining qty, and status badge
- [ ] Movement history unified view covers all 4 source types
- [ ] Movement type filter isolates specific source types
- [ ] Admin sees all stores; Sales Rep with `reports.view_own` sees only their store
- [ ] All sections are independently paginated
- [ ] Empty state rendered per section when no data matches filters

## Dependencies
- `product_variants`, `products`, `categories`, `brands`
- `stock_batches` (Phase 3)
- `stock_adjustments` (Phase 3/5)
- `stock_transfer_items` (Phase 5)
- `reception_order_product` (Phase 5)
- `sales_order_items` (Phase 4)
- Spatie Permission: `reports.view_all`, `reports.view_own`

## Notes
- No new tables — movement history built via UNION query or collection merge
- Movement history is the most complex query; consider a database view or dedicated query class
- Stock quantity at a point in time (historical) is out of scope for v1 — show current state only
