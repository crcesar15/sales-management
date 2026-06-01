# Task 02 — POS Interface

## What
The primary point-of-sale screen where Sales Reps process transactions. Combines product search, cart management, customer selection, discount/tax calculation, split payments, and checkout into a single reactive interface. Requires an open cash register shift to operate.

## Why
Replaces manual order entry with a fast, keyboard-and-scanner-friendly UI that reduces checkout time and errors. Shift tracking ensures cash accountability at the register level.

## Requirements
- **Shift gate**: POS requires an open cash register shift before any sale can be processed. If no shift is open, the cashier must open one before proceeding.
- Product search by name, SKU, or barcode (debounced, fast)
- Barcode scanner support: auto-submit on Enter keypress
- Cart: add, change quantity, remove items
- Each cart item: product variant + sale unit (base/bulk), quantity, unit price, line total
- Order-level discount: flat amount or percentage
- Tax: single global rate from `settings`, applied on `(subtotal − discount)`
- Split payments: multiple payment methods per transaction (e.g. $50 cash + $25 card), sum must equal total
- Hold/resume orders: park the current cart, start a new transaction, resume the held order later
- Customer selection: optional autocomplete or "Walk-in"
- Responsive layout: desktop and tablet
- On checkout: creates `sales_order` + `sales_order_items` + `sales_order_payments`, deducts stock via FIFO, links to `cash_register_shift_id`

## Acceptance Criteria
- [ ] Barcode scan (Enter-terminated) adds product to cart instantly
- [ ] Cart updates are reactive with correct totals
- [ ] Discount (flat and %) adjusts total correctly
- [ ] Tax is calculated on discounted subtotal
- [ ] Checkout requires an open shift; returns 422 if shift is missing or closed
- [ ] Split payments: sum of all payment amounts must equal order total; returns 422 if mismatch
- [ ] Hold order creates `sales_order` with status `held` (no stock deduction)
- [ ] Resume held order transitions to `draft` and populates cart
- [ ] Checkout creates order and stock deduction atomically (DB transaction)
- [ ] Walk-in checkout completes without a customer
- [ ] POS state survives component re-renders (Pinia)
- [ ] Works on tablet viewport (768px+)
- [ ] Shift info (register name, cashier, opening balance) is displayed in POS header

## Dependencies
- `products`, `product_variants`, `sale_units` (via `product_variant_units`) tables
- `batches` — FIFO deduction (note: actual table name is `batches`, not `inventory_batches`)
- `customers` — optional search
- `settings` — tax rate (`tax_rate` in group `tax`), store info
- `sales_orders`, `sales_order_items`, `sales_order_payments` — created on checkout
- `cash_registers`, `cash_register_shifts` — shift must be open to sell
- Pinia store for cart state

## Notes
- POS does not use the standard Inertia page layout (full-screen, no sidebar)
- Cart state is ephemeral (Pinia, not persisted to DB until checkout)
- FIFO deduction must be wrapped in a DB transaction with pessimistic locking
- `sale_units` refers to `product_variant_units` with `type='sale'` in the codebase
- `inventory_batches` refers to the `batches` table in the codebase
- Held orders are stored in `sales_orders` with `status='held'`; items are stored in `sales_order_items` but no stock is deducted until checkout