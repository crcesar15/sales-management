# Task 03 — Stock Transfers

## What
A full inter-store transfer workflow. Stock is requested from one store, picked, shipped,
received, and reconciled at the destination — with discrepancy support.

## Why
Retail chains redistribute stock between branches. Transfers must be traceable, auditable,
and able to handle partial receipts where quantities don't match what was sent.

## Requirements
- [ ] Full status workflow: `requested` → `picked` → `in_transit` → `received` → `completed` (or `cancelled`)
- [ ] Create transfer: select source store, destination store, variants + quantities
- [ ] Each status transition logged via Spatie activity log
- [ ] On `completed`: deduct from source store batches (FIFO), create new batch at destination
- [ ] Partial transfers: `quantity_received` can differ from `quantity_sent`
- [ ] `from_store` and `to_store` must be different
- [ ] Requires `stock.adjust` permission (or dedicated `stock.transfer` permission)
- [ ] Admin can view all transfers; Sales Rep sees only transfers involving their store

## Acceptance Criteria
- [ ] Transfer cannot be created with `from_store_id = to_store_id`
- [ ] Status transitions follow the defined workflow (no skipping states)
- [ ] Completing a transfer deducts source FIFO batches and creates destination batch
- [ ] Discrepancy is recorded when `quantity_received != quantity_sent`
- [ ] All transitions appear in activity log

## Dependencies
| Dependency | Notes |
|---|---|
| `batches` table + `BatchService` | FIFO deduction at completion |
| `stores` | Source and destination |
| `product_variants` | Items being transferred |
| `spatie/laravel-activitylog` | Audit trail for all transitions |
| `spatie/laravel-permission` | `stock.adjust` or `stock.transfer` permission |

## New Tables
- `stock_transfers`
- `stock_transfer_items`

## Notes
- Cancellation is allowed from any state before `completed`
- A new batch created at destination uses `initial_quantity = quantity_received`
- The destination batch `reception_order_id` may be nullable (or reference a virtual origin)
