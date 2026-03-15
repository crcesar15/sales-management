# Testing — POS Interface

## Test File Locations
```
tests/Feature/PosCheckoutTest.php
tests/Feature/PosProductSearchTest.php
tests/Unit/Services/CheckoutServiceTest.php
tests/Unit/Services/FifoStockDeductionTest.php
```

## Feature Test Cases

### Product Search (`/pos/products/search`)
- Returns products matching partial name
- Returns product matching exact barcode
- Returns product matching SKU prefix
- Returns max 15 results
- User without `sales.create` gets 403

### Checkout (`POST /pos/checkout`)
- Valid cart creates `sales_order` with correct totals
- Valid cart creates correct `sales_order_items` records
- Stock is deducted from oldest batch first (FIFO)
- Walk-in checkout (no `customer_id`) succeeds
- Checkout with known `customer_id` attaches customer
- Flat discount reduces total correctly
- Percentage discount reduces total correctly
- Tax is applied on discounted subtotal only
- Request with empty `items` array returns 422
- Request with invalid `product_variant_id` returns 422
- Checkout fails gracefully when insufficient stock (no partial commit)
- Response includes `receipt_token` and `sales_order_id`

### Unit: `CheckoutService`
- Totals calculated correctly for flat discount
- Totals calculated correctly for percentage discount
- Tax calculation uses server-side rate, not client value

### Unit: `FifoStockDeductionService`
- Deducts from oldest batch first
- Spans multiple batches when first is insufficient
- Throws `InsufficientStockException` when total stock is insufficient
- Does not mutate batches if stock insufficient (transaction rollback)

## Coverage Goals
- All checkout paths (with/without customer, both discount types)
- FIFO edge cases (single batch, multi-batch, exact depletion, shortage)
- Permission enforcement on all endpoints
