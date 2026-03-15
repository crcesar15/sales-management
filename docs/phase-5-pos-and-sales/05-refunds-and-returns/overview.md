# Task 05 — Refunds & Returns

## What
Allows partial or full returns on paid sales orders. A Sales Rep creates a refund request; an authorised user approves or rejects it. Approved and completed refunds restock the returned items.

## Why
Enables formal return handling with approval workflow and audit trail, while ensuring inventory accuracy is maintained.

## Requirements
- Returns tied to a specific `paid` sales_order
- Partial returns: return a subset of items / quantities
- New tables: `refunds`, `refund_items`
- Status workflow: `pending` → `approved` → `completed` (or `rejected`)
- `refunds.manage` permission required to approve/reject/complete
- Sales Reps can create refund requests (`sales.create` sufficient)
- On `completed`: restock returned items — create new `inventory_batch` record
- Refunded amount is tracking only (no payment gateway integration)
- Activity log on all status changes
- Store-scoped: refund belongs to the same store as the sales order

## Acceptance Criteria
- [ ] Sales Rep can submit a refund request for a paid order
- [ ] Admin/manager can approve, reject, or complete a refund
- [ ] Partial quantity returns are supported per line item
- [ ] On completion, a new inventory batch is created for each returned variant
- [ ] Refund amount is calculated and stored (not processed)
- [ ] All status changes appear in the activity log
- [ ] Cannot return more than the original quantity per line
- [ ] Cannot create a duplicate pending refund for the same order (guard)

## Dependencies
- `sales_orders`, `sales_order_items`
- `inventory_batches` — new batch on completion
- `spatie/laravel-activitylog`
- `spatie/laravel-permission`

## Notes
- `completed` status is the trigger for restocking — not `approved`
- A single order may have multiple refunds over time (partial refunds)
- Quantity validation: `sum of all refund quantities + new refund quantities ≤ original quantity`
