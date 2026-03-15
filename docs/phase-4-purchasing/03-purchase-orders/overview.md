# Task 03: Purchase Orders — Overview

## What
Global purchase orders raised against vendors, with a multi-step approval/fulfillment workflow.

## Why
Formalizes the purchasing process with approval gates, audit trail, and a clear link from vendor catalog to received stock.

## Requirements
- Purchase orders are global (not store-scoped); destination store is set at reception time
- Status workflow: `draft → awaiting_approval → approved → sent → paid → cancelled`
- Only variants present in the vendor's **active** catalog entries can be added as line items
- Price is snapshotted from the catalog at creation time
- Cancellation allowed from `draft`, `awaiting_approval`, `approved`; blocked after `sent` or `paid`
- Totals (sub_total, discount, total) computed server-side; frontend sends quantities only
- All status transitions logged via Spatie Activity Log

## Acceptance Criteria
- [ ] PO can only be created by users with `purchase_orders.create`
- [ ] Approval (draft → awaiting_approval → approved) requires `purchase_orders.approve`
- [ ] Line items are restricted to the selected vendor's active catalog
- [ ] Price snapshot is stored on `purchase_order_product`, not re-fetched from catalog
- [ ] Cancellation button is hidden/disabled after `sent` or `paid`
- [ ] All status transitions appear in the activity log
- [ ] Sub-total, discount, and total are recalculated on every save

## Dependencies
- `vendors` table (Task 01)
- `catalog` table (Task 02) — for line item selection and price snapshot
- `product_variants` table (Phase 1)
- Phase 4 Task 04 (Reception Orders) creates receptions against a PO
- `spatie/laravel-activitylog`
- `spatie/laravel-permission`

## Notes
- No partial saves for line items; PO is saved atomically (DB transaction)
- `proof_of_payment_type` and `proof_of_payment_number` are informational only
- `discount` is applied at PO level (flat amount), not per line item
