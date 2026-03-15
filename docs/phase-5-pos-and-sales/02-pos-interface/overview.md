# Task 02 — POS Interface

## What
The primary point-of-sale screen where Sales Reps process transactions. Combines product search, cart management, customer selection, discount/tax calculation, and checkout into a single reactive interface.

## Why
Replaces manual order entry with a fast, keyboard-and-scanner-friendly UI that reduces checkout time and errors.

## Requirements
- Product search by name, SKU, or barcode (debounced, fast)
- Barcode scanner support: auto-submit on Enter keypress
- Cart: add, change quantity, remove items
- Each cart item: product variant + sale unit (base/bulk), quantity, unit price, line total
- Order-level discount: flat amount or percentage
- Tax: single global rate from `settings`, applied on `(subtotal − discount)`
- Payment methods: `cash`, `credit_card`, `qr`, `transfer` (hardcoded)
- Customer selection: optional autocomplete or "Walk-in"
- Responsive layout: desktop and tablet
- On checkout: creates `sales_order` + `sales_order_items`, deducts stock via FIFO

## Acceptance Criteria
- [ ] Barcode scan (Enter-terminated) adds product to cart instantly
- [ ] Cart updates are reactive with correct totals
- [ ] Discount (flat and %) adjusts total correctly
- [ ] Tax is calculated on discounted subtotal
- [ ] Checkout creates order and stock deduction atomically (DB transaction)
- [ ] Walk-in checkout completes without a customer
- [ ] POS state survives component re-renders (Pinia)
- [ ] Works on tablet viewport (768px+)

## Dependencies
- `products`, `product_variants`, `sale_units` tables
- `inventory_batches` — FIFO deduction
- `customers` — optional search
- `settings` — tax rate, store info
- `sales_orders`, `sales_order_items` — created on checkout
- Pinia store for cart state

## Notes
- POS does not use the standard Inertia page layout (full-screen, no sidebar)
- Cart state is ephemeral (Pinia, not persisted to DB until checkout)
- FIFO deduction must be wrapped in a DB transaction with pessimistic locking
