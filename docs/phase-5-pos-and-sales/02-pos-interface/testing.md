# Testing — POS Interface

## Test File Locations
```
tests/Feature/PosCheckoutTest.php
tests/Feature/PosProductSearchTest.php
tests/Feature/PosHoldResumeTest.php
tests/Unit/Services/CheckoutServiceTest.php
tests/Unit/Services/FifoStockDeductionTest.php
```

## Feature Test Cases

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
- Stock is deducted from oldest batch first (FIFO)
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
- Totals calculated correctly for flat discount
- Totals calculated correctly for percentage discount
- Tax calculation uses server-side rate from settings, not client value
- Split payment total validation matches computed order total

### FifoStockDeductionService
- Deducts from oldest batch first
- Spans multiple batches when first is insufficient
- Throws `InsufficientStockException` when total stock is insufficient
- Does not mutate batches if stock insufficient (transaction rollback)
- Calls `ProductVariant::recalculateStock()` for affected variants after deduction

## Coverage Goals
- All checkout paths: with/without customer, both discount types, single and split payments
- FIFO edge cases: single batch, multi-batch, exact depletion, shortage
- Permission enforcement on all endpoints
- Shift gate validation: no shift, closed shift, wrong user's shift
- Hold/resume lifecycle: create held, resume, complete checkout