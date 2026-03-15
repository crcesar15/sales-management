# Task 04 — Stock Adjustments

## What
Manual inventory corrections applied directly to batches (or creating a correction batch),
with mandatory reason tracking and full audit trail.

## Why
Physical audits, damage, theft, and data-entry errors require occasional manual corrections.
Every adjustment must be traceable to a user, a reason, and a specific time — for both
operational accuracy and loss-prevention reporting.

## Requirements
- [ ] Create an adjustment: select variant, store, quantity change (positive or negative), reason, optional notes
- [ ] Optionally link adjustment to a specific batch; if none specified, apply to oldest active batch (FIFO)
- [ ] Reason types: `physical_audit`, `robbery`, `expiry`, `damage`, `correction`, `other`
- [ ] Updates `remaining_quantity` on the target batch
- [ ] Every adjustment logged via Spatie activity log
- [ ] Requires `stock.adjust` permission
- [ ] Admin can view all adjustments; Sales Rep (with permission) can create and view only their own

## Acceptance Criteria
- [ ] Negative adjustment cannot reduce `remaining_quantity` below 0
- [ ] Adjustment correctly updates `remaining_quantity` on the batch
- [ ] `reason` field is mandatory and must be a valid enum value
- [ ] Activity log entry created for every adjustment (with user, reason, delta)
- [ ] Admin sees all adjustments; restricted user sees only their own

## Dependencies
| Dependency | Notes |
|---|---|
| `batches` table | Target for adjustments |
| `product_variants` | Identifies what stock is being adjusted |
| `stores` | Location of the stock |
| `users` | Tracks who made the adjustment |
| `spatie/laravel-activitylog` | Audit trail |
| `spatie/laravel-permission` | `stock.adjust` permission |

## New Table
- `stock_adjustments`

## Notes
- If `batch_id` is not specified, select the oldest `active` batch for the variant+store
- If no active batch exists, a `correction` batch can be created (for positive adjustments only)
- Adjustments are permanent — no undo. Reverse with an opposite adjustment if needed
