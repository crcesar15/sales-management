# Task 01 — Stock Overview

## What
A read-only view of current stock levels across all stores, derived from the `batches` table.
Displays per-store and global stock totals per product variant, with low-stock indicators.

## Why
Operators need a single screen to monitor inventory health — which variants are running low,
which stores have stock, and what the global position is — without digging into individual batches.

## Requirements
- [ ] Show stock per store and global totals for every product variant
- [ ] Aggregate `remaining_quantity` from `active` and `queued` batches only
- [ ] Display: product name, variant identifier, per-store quantities, global total
- [ ] Low-stock indicator when stock falls below `minimum_stock_level`
- [ ] Filter by: store, category, brand, low-stock flag
- [ ] Accessible to Admin and Sales Rep (no special permission gate)
- [ ] Add `store_id` to `batches` table (new migration)

## Acceptance Criteria
- [ ] Stock totals match SUM of `remaining_quantity` from active/queued batches per store
- [ ] Global column = sum across all stores
- [ ] Low-stock badge appears when `total_stock < minimum_stock_level`
- [ ] Filters work independently and in combination
- [ ] Page loads without N+1 queries (eager-loaded, aggregated via query)

## Dependencies
| Dependency | Notes |
|---|---|
| `batches` table | Core data source; needs `store_id` migration (this task) |
| `product_variants` | Needs `minimum_stock_level` column (added in Task 05) |
| `stores` | For per-store breakdown |
| `categories`, `brands` | For filter options |

## New Migrations
- `add_store_id_to_batches_table` — adds `store_id` (FK → `stores`) to `batches`

## Notes
- Batches are **created** in Phase 4 (reception orders), **consumed** in Phase 5 (sales)
- `sold_quantity` on batches is decremented by the sales flow (Phase 5)
- `missing_quantity` records discrepancies found during stock audits
- For v1, stock is query-computed on demand — no denormalized cache
