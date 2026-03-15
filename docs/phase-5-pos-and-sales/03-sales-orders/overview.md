# Task 03 — Sales Orders

## What
The core sales transaction record. Covers both POS-generated orders (instant checkout) and manually created orders (quote → paid workflow).

## Why
Provides a complete, auditable record of every sale, linking products, quantities, prices, stock changes, and payment info to a specific store and user.

## Requirements
- `sales_orders`: tracks header-level data (customer, user, store, status, payment, totals)
- `sales_order_items`: line items with snapshot pricing and conversion factor
- Status workflow:
  - POS path: `draft` → `paid` (immediate)
  - Manual path: `draft` → `sent` → `paid`
  - Any path: → `cancelled`
- Manual order creation requires `sales.manage` permission
- Sales history visibility:
  - `sales.view_all` → see all orders
  - Default → see only own orders (`user_id = auth()->id()`)
- FIFO stock deduction fires on transition to `paid`
- All orders are scoped to a `store_id`

## Acceptance Criteria
- [ ] POS checkout creates `paid` order atomically
- [ ] Manual orders can progress through `draft → sent → paid`
- [ ] Cancelling an order does NOT restock (refund flow handles that)
- [ ] `sales.view_all` users see all store orders; others see only their own
- [ ] `tax_amount` and `sub_total` are stored (snapshot) on the order
- [ ] `sales_order_items.conversion_factor` is snapshotted at time of sale
- [ ] Store-scoped: orders tied to the active store

## Dependencies
- `customers`, `users`, `stores` tables
- `product_variants`, `sale_units` tables
- `inventory_batches` — FIFO deduction on paid
- `spatie/laravel-activitylog` — status change audit

## Notes
- `sent` status = order sent to customer as quote/invoice before payment
- Cancellation is a status only — no automatic stock adjustment
- `store_id` FK added via new migration (see database.md)
