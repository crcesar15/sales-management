# Task 02 — Batch Tracking

## What
Visibility and management of individual inventory batches — each linked to a reception order,
a product variant, and a store. Includes expiry tracking and lifecycle management.

## Why
Stock accuracy depends on knowing which physical batch is being consumed. FIFO order,
expiry monitoring, and per-batch quantities are all batch-level concerns.

## Requirements
- [ ] View list of batches with filters (status, product, store, expiry range)
- [ ] View individual batch details: variant, reception order, all quantity fields, expiry status
- [ ] Batch lifecycle: `queued` → `active` → `closed`
- [ ] Expiry alerts when `expiry_date` is within `expiry_alert_days` (from settings)
- [ ] Manual close action (Admin only, requires `stock.adjust` permission)
- [ ] Batches consumed FIFO during sales (oldest `created_at` / `queued` batch first)
- [ ] Admin-only to view batch details; `stock.adjust` to modify

## Acceptance Criteria
- [ ] Batch list shows status, quantities, expiry, store, and variant
- [ ] Expiry badge shown for batches expiring within threshold
- [ ] Manual close sets `status = closed` and logs the action
- [ ] FIFO consumption order is enforced in batch selection logic
- [ ] Filters work: status, product variant, store, expiry range

## Dependencies
| Dependency | Notes |
|---|---|
| `batches.store_id` | Added in Task 01 migration |
| `settings` table | `expiry_alert_days` threshold |
| `reception_orders` | Batches are created here (Phase 4) |
| `spatie/laravel-activitylog` | Log manual close actions |
| `spatie/laravel-permission` | `stock.adjust` permission gate |

## Cross-Phase Notes
- Batch **creation** happens in Phase 4 (reception orders completed)
- Batch **consumption** (`sold_quantity++`, `remaining_quantity--`) happens in Phase 5 (sales)
- Lifecycle is managed here; other phases interact with batches but don't own the lifecycle

## Notes
- `queued` → `active` transition is automatic: first sale deduction marks batch active
- `active` → `closed` is automatic when `remaining_quantity = 0`, or manual by Admin
- Expiry date is set at reception and is immutable after creation
