# Testing — Sales Orders

## Test File Locations
```
tests/Feature/SalesOrderListTest.php
tests/Feature/SalesOrderStatusTest.php
tests/Feature/ManualOrderCreationTest.php
tests/Unit/Models/SalesOrderTest.php
tests/Unit/Services/SalesOrderServiceTest.php
```

## Feature Test Cases

### List & Visibility
- User with `sales.view` sees only their own orders
- User with `sales.view_all` sees all orders in the store
- Filters by status return correct subset
- Date range filter returns orders within range
- Search by customer name returns matching orders
- Orders from other stores are never returned

### Order Creation (Manual)
- User with `sales.manage` can create a draft order
- User without `sales.manage` gets 403
- Created order has correct `sub_total`, `discount`, `tax_amount`, `total`
- `token` UUID is auto-generated and unique on creation
- `store_id` is assigned from the authenticated user's store

### Status Transitions
- `draft` → `sent` succeeds
- `draft` → `paid` triggers FIFO stock deduction
- `sent` → `paid` triggers FIFO stock deduction
- `paid` → `cancelled` does NOT re-stock
- Invalid transition (e.g. `paid` → `draft`) returns 422
- Status changes are logged via activity log

### Stock Deduction (Integration)
- Transitioning to `paid` reduces `inventory_batches.quantity_remaining` correctly
- Transition to `paid` fails atomically if stock is insufficient

## Unit Test Cases

### `SalesOrderTest`
- `token` is set on `creating` event
- `displayCustomer` returns "Walk-in" when customer is null

### `SalesOrderServiceTest`
- Allowed transitions match spec for each status
- Disallowed transition throws correct exception

## Coverage Goals
- All status transitions (valid and invalid)
- Visibility scoping (own vs all)
- Stock deduction on paid transition
- Permission gating on all mutations
