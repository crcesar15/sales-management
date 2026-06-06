# Task 02 — Sales Orders

## Prerequisites
- **Task 01b (Cash Registers & Shifts)** must be complete — this task depends on `cash_registers`, `cash_register_shifts`, and `cash_register_movements` tables and models

## What
The core sales transaction record. Covers POS-generated orders (instant checkout), manually created orders (quote → paid workflow), held orders (parked for later), and split payments across multiple methods.

## Why
Provides a complete, auditable record of every sale, linking products, quantities, prices, payments, stock changes, and cash register shifts to a specific store and user.

## Requirements
- `sales_orders`: tracks header-level data (customer, user, store, shift, status, discount, totals)
- `sales_order_items`: line items with snapshot pricing and conversion factor
- `sales_order_payments`: multiple payment records per order (split payments)
- Status workflow:
  - POS path: `draft` → `paid` (immediate checkout)
  - POS hold: `draft` → `held` → `draft` → `paid` (resume and checkout)
  - Manual path: `draft` → `sent` → `paid`
  - Any path: → `cancelled`
- Manual order creation requires `sales.manage` permission
- Sales history visibility:
  - `sales.view_all` → see all orders
  - Default → see only own orders (`user_id = auth()->id()`)
- FIFO stock deduction fires on transition to `paid`
- All orders are scoped to a `store_id`
- POS orders are linked to a `cash_register_shift_id`
- Split payments: each order can have multiple `sales_order_payment` records whose amounts sum to the total

## Acceptance Criteria
- [ ] POS checkout creates `paid` order atomically with items and payments
- [ ] Manual orders can progress through `draft → sent → paid`
- [ ] Held orders can be created and resumed without stock deduction
- [ ] Cancelling an order does NOT restock (refund flow handles that)
- [ ] `sales.view_all` users see all store orders; others see only their own
- [ ] `tax_amount` and `sub_total` are stored (snapshot) on the order
- [ ] `sales_order_items.conversion_factor` is snapshotted at time of sale
- [ ] `sales_order_payments` records are created for each payment method
- [ ] Payment amounts sum to order total
- [ ] Store-scoped: orders tied to the active store
- [ ] POS-created orders have `cash_register_shift_id` set
- [ ] `payment_method` on `sales_orders` is informational; actual payment records are in `sales_order_payments`

## Permissions
| Permission | Scope |
|---|---|
| `sales.view` | View own orders and order detail |
| `sales.view_all` | View all orders in the store |
| `sales.create` | Create orders (POS checkout, hold) |
| `sales.manage` | Create manual orders, transition status, cancel |

## Dependencies
- `customers`, `users`, `stores` tables
- `product_variants`, `product_variant_units` (sale units) tables
- `batches` — FIFO deduction on paid
- `cash_registers`, `cash_register_shifts` — shift linking for POS orders (from Task 01b)
- `spatie/laravel-activitylog` — status change audit

## Follow-Up for Task 01b
- Add `salesOrders()` HasMany relationship to `CashRegisterShift` model
- Update `CashRegisterShiftService::closeShift()` to include cash sales from `sales_order_payments` in `expected_closing` calculation

## Notes
- `sent` status = order sent to customer as quote/invoice before payment
- `held` status = POS-parked order, no stock deducted until resumed and checked out
- Cancellation is a status only — no automatic stock adjustment (refund flow handles that)
- `store_id` FK added via migration (see database.md)
- `payment_method` on `sales_orders` serves as a default/informational field; the actual payment breakdown is in `sales_order_payments`
- **PaymentMethod enum discrepancy**: `App\Enums\PaymentMethod` currently defines `{bank_transfer, cash, check, credit_card}` but needs to be updated to `{cash, credit_card, qr, transfer}` to match the database and POS requirements. This is a code change to be done during implementation.
- **Table name clarifications**: `inventory_batches` in earlier docs refers to the `batches` table; `sale_units` refers to `product_variant_units` with `type='sale'`
- **FifoStockDeductionService** and **InsufficientStockException** are defined in this task and used by both this task (manual order status transitions) and Task 03 (POS Interface) via `SalesOrderService::transitionStatus()`