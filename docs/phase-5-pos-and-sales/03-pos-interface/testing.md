# Testing — POS Interface

## Test File Locations
```
tests/Feature/PosCheckoutTest.php
tests/Feature/PosProductSearchTest.php
tests/Feature/PosHoldResumeTest.php
tests/Feature/PosSessionTest.php
tests/Unit/Services/CheckoutServiceTest.php
```

> **Note**: `FifoStockDeductionService` unit tests are in Task 02 (Sales Orders), not here. `CheckoutService` tests focus on shift gate validation and delegation to `SalesOrderService`.

## Feature Test Cases

### Session Management (`GET/POST/PATCH /api/v1/pos/session/*`)
- Authenticated user can get current POS session
- User can list registers for their store
- User can select a register for the session
- User with `shift.open` permission can open a shift
- User with `shift.close` permission can close their own shift
- User with `cash_movement.create` can add cash in/out movements
- User without `pos.access` gets 403 on session endpoints

### Product Search (`GET /api/v1/pos/products/search`)
- Returns products matching partial name
- Returns product matching exact barcode
- Returns product matching SKU prefix
- Returns max 15 results
- Only returns `active` sale units (not `inactive`)
- Includes stock quantity from `batches` for the user's store
- User without `sales.create` gets 403

### Checkout (`POST /api/v1/pos/checkout`)
- Valid cart creates `sales_order` with status `paid` and correct totals
- Valid cart creates correct `sales_order_items` records with snapshotted `conversion_factor`
- Valid cart creates correct `sales_order_payments` records for each payment method
- `cash_register_shift_id` is stored on the `sales_order`
- Stock is deducted from oldest batch first (FIFO) — via `SalesOrderService::create()` which calls `FifoStockDeductionService`
- Walk-in checkout (no `customer_id`) succeeds
- Checkout with known `customer_id` attaches customer
- Flat discount reduces total correctly
- Percentage discount reduces total correctly
- Tax is applied on discounted subtotal only
- Split payment: two payment methods, sum equals total — succeeds
- Split payment: sum does not equal total — returns 422
- Request with empty `items` array returns 422
- Request with invalid `product_variant_id` returns 422
- Checkout without `cash_register_shift_id` returns 422
- Checkout with a closed shift returns 422
- Checkout with a shift belonging to another user returns 422
- Checkout fails gracefully when insufficient stock (no partial commit — full rollback)
- Response includes `receipt_token` and `sales_order_id`

### Hold Order (`POST /api/v1/pos/hold`)
- Valid cart creates `sales_order` with status `held`
- Held order items are stored in `sales_order_items`
- No stock is deducted for held orders
- No payment records created for held orders
- `cash_register_shift_id` is stored on the held order
- Response includes `sales_order_id` and `token`

### Resume Order (`POST /api/v1/pos/resume/{id}`)
- Held order transitions to status `draft`
- Response includes full order with items for cart population
- Attempting to resume a non-held order returns 422
- Attempting to resume another user's held order returns 422

### Held Orders List (`GET /api/v1/pos/held-orders`)
- Returns held orders for the current user's open shift
- Does not include orders from other shifts
- Does not include non-held orders

## Unit Test Cases

### CheckoutService
- Shift gate validation: rejects checkout when no shift is open
- Shift gate validation: rejects checkout when shift belongs to another user
- Shift gate validation: rejects checkout when shift is closed
- Delegates to `SalesOrderService::create()` with correct data for checkout
- Delegates to `SalesOrderService::holdOrder()` for hold orders
- Delegates to `SalesOrderService::resumeOrder()` for resume operations

> Total calculation tests (`calculateTotals`) and FIFO deduction tests are in Task 02 (Sales Orders) since `SalesOrderService` and `FifoStockDeductionService` are defined there.

## Coverage Goals
- All checkout paths: with/without customer, both discount types, single and split payments
- Shift gate validation: no shift, closed shift, wrong user's shift
- Hold/resume lifecycle: create held, resume, complete checkout
- Permission enforcement on all endpoints
- Session management: register selection, shift open/close, movements