# Task 04: Reception Orders — Overview

## What
Records actual goods received against a purchase order and updates stock + batch inventory.

## Why
Receiving closes the loop between what was ordered and what physically arrived. It also feeds the batch/expiry tracking system.

## Requirements
- One purchase order can have multiple partial receptions
- Each reception targets a specific **store** (destination for received stock)
- New `store_id` column added to `reception_orders` table via migration
- Status values: `pending`, `uncompleted`, `completed`, `cancelled`
- On **completion**:
  1. For each line item: `stock += quantity × conversion_factor`
  2. Auto-create a batch per line item with `initial_quantity = quantity × conversion_factor`
  3. Batch `store_id` = reception's `store_id`
  4. Optional expiry date per batch set at reception time
- Requires `reception_orders.manage` permission
- Completion is logged via Spatie Activity Log

## Acceptance Criteria
- [ ] Migration adds `store_id` FK to `reception_orders` table
- [ ] Reception can only be created against a PO in `approved` or `sent` status
- [ ] Partial receptions allowed; quantities need not match PO line items exactly
- [ ] Stock and batch records are updated atomically (DB transaction) on completion
- [ ] `conversion_factor` from the catalog entry is applied at stock update time
- [ ] Expiry date is optional per batch line item
- [ ] Activity log records completion event with user and timestamp

## Dependencies
- `purchase_orders` table (Task 03)
- `vendors` table (Task 01)
- `stores` table (Phase 1)
- `product_variants` table (Phase 1)
- `batches` table (Phase 2 — stock/batch management)
- `catalog` table (Task 02) — for `conversion_factor` lookup
- `spatie/laravel-activitylog`

## Notes
- Completing a reception does NOT automatically move the PO to `paid`
- Cancelling a reception does not reverse already-completed stock adjustments
- Multiple completed receptions on the same PO are valid (partial delivery scenario)
