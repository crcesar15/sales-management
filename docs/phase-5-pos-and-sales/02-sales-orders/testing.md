# Testing — Sales Orders

## Test File Locations
```
tests/Feature/SalesOrderTest.php
tests/Feature/SalesOrderApiTest.php
tests/Unit/Models/SalesOrderTest.php
tests/Unit/Services/SalesOrderServiceTest.php
tests/Unit/Services/FifoStockDeductionTest.php
```

## Feature Test Cases

### Listing & Visibility
- Authenticated user with `sales.view` can access order list
- User with `sales.view_all` sees all orders in the store
- User without `sales.view_all` sees only their own orders (`user_id = auth()->id()`)
- Orders are scoped to the user's store — no cross-store orders visible
- List is paginated and filterable by status, date range, search
- `held` orders appear when filtering by status `held`

### Create (Manual Order)
- Valid payload creates a `draft` order with items and payments
- Totals are calculated server-side (not from client values)
- `token` UUID is auto-generated on creation
- `store_id` is set from the authenticated user's store
- User without `sales.manage` gets 403
- Split payment: multiple payment records created, sum equals total

### Status Transitions
- `draft → sent` succeeds
- `draft → paid` succeeds (triggers FIFO deduction)
- `draft → held` succeeds (no stock deduction)
- `draft → cancelled` succeeds
- `held → draft` succeeds (resume)
- `held → cancelled` succeeds
- `sent → paid` succeeds (triggers FIFO deduction)
- `sent → cancelled` succeeds
- `paid → cancelled` succeeds
- Invalid transitions (e.g. `paid → draft`, `cancelled → paid`) return 422

### POS Checkout (`POST /pos/checkout`)
- Creates `paid` order with items and payments atomically
- `cash_register_shift_id` is stored on the order
- `sales_order_payments` records are created for each payment method
- Split payment: two methods, sum equals total — succeeds
- Split payment: sum does not equal total — returns 422
- Walk-in checkout (no `customer_id`) succeeds
- Checkout without open shift returns 422
- Checkout with closed shift returns 422
- Checkout with shift belonging to another user returns 422

### Hold/Resume
- `POST /pos/hold` creates order with status `held`, items stored, no stock deducted
- No `sales_order_payments` records created for held orders
- `GET /pos/held-orders` returns held orders for current shift
- `POST /pos/resume/{id}` transitions `held` → `draft`, returns order with items
- Resuming a non-held order returns 422
- Resuming another user's held order returns 422

### Cancel
- Cancelling an order sets status to `cancelled`
- NO stock reversal occurs on cancellation
- Cancelling an already cancelled order returns 422
- Cancelled order is still visible in the list (with `cancelled` status badge)

### Edge Cases
- Empty `items` array returns 422
- Invalid `product_variant_id` returns 422
- Insufficient stock fails gracefully — no partial commit (transaction rollback)
- `conversion_factor` is snapshotted on `sales_order_items` at creation time

## Unit Test Cases

### SalesOrder Model
- `token` UUID is auto-generated on `creating` event
- `customer()` returns BelongsTo relationship (nullable)
- `user()`, `store()`, `shift()` return BelongsTo relationships
- `items()` returns HasMany SalesOrderItem
- `payments()` returns HasMany SalesOrderPayment
- `scopeHeld()` filters to status = held
- `scopeNotHeld()` excludes held orders

### SalesOrderService
- `calculateTotals()` — flat discount calculates correctly
- `calculateTotals()` — percentage discount calculates correctly
- `calculateTotals()` — tax applied on discounted subtotal
- `calculateTotals()` — zero discount returns full subtotal + tax
- `transitionStatus()` — valid transitions succeed
- `transitionStatus()` — invalid transitions throw `InvalidArgumentException`
- `holdOrder()` — creates order with status = held, no stock deducted
- `resumeOrder()` — transitions held → draft
- `cancel()` — sets status to cancelled, no stock reversal

### FifoStockDeductionService
- Deducts from oldest batch first
- Spans multiple batches when first is insufficient
- Throws `InsufficientStockException` when total stock is insufficient
- Does not mutate batches if stock is insufficient (transaction rollback)
- Calls `ProductVariant::recalculateStock()` for each affected variant

## Coverage Goals
- 100% of controller actions covered by feature tests
- All status transitions tested (valid and invalid)
- Split payment creation and validation
- Hold/resume lifecycle
- Visibility scope enforcement
- FIFO deduction edge cases
- Permission gating on all mutations

## Cross-Task Integration Tests
- `CashRegisterShiftService::closeShift()` includes cash sales from `sales_order_payments` in `expected_closing` calculation
- `CashRegisterShift` model has `salesOrders()` HasMany relationship working correctly