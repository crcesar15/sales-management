# Testing — Refunds & Returns

## Test File Locations
```
tests/Feature/RefundCreationTest.php
tests/Feature/RefundStatusTest.php
tests/Unit/Services/RefundServiceTest.php
tests/Unit/Models/RefundTest.php
```

## Feature Test Cases

### Create Refund Request
- Sales Rep with `sales.create` can create a refund for a paid order
- Cannot create refund for a non-paid (draft/sent) order
- Partial refund (subset of items) is accepted
- Full refund (all items, full quantities) is accepted
- `quantity_returned` exceeding original quantity returns 422
- `quantity_returned` exceeding remaining returnable qty (accounting for prior refunds) returns 422
- `sales_order_item_id` not belonging to the target order returns 422
- `total_refund` is correctly calculated and stored on creation
- Empty `items` array returns 422
- User without any permission gets 403

### Status Transitions
- `pending` → `approved` succeeds for user with `refunds.manage`
- `pending` → `rejected` succeeds for user with `refunds.manage`
- `approved` → `completed` triggers restock (new inventory batch per item)
- `approved` → `rejected` succeeds (no restock)
- Invalid transition (e.g. `completed` → `pending`) returns 422
- User without `refunds.manage` cannot transition status (403)
- Every transition is recorded in the activity log

### Restock on Completion
- `completed` creates one new `inventory_batch` per refund item
- New batch `quantity` and `quantity_remaining` match `quantity_returned`
- Batch is created with correct `store_id` and `product_variant_id`
- No batch is created on `approved` or `rejected`

### List & Visibility
- User with `refunds.manage` sees all store refunds
- Sales Rep without `refunds.manage` sees only their own refund requests
- Refunds from other stores are never returned

## Unit Test Cases

### `RefundServiceTest`
- Valid status transitions succeed
- Invalid transitions throw correct exception
- Restock logic creates correct inventory batch attributes

### `RefundTest`
- `total_refund` equals sum of `refund_items.line_refund`

## Coverage Goals
- All status transitions (valid and invalid)
- Returnable quantity validation (fresh and cumulative)
- Restock batch creation verified
- Permission enforcement on create and status update
