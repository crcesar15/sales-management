# Task 04 — Testing: Stock Adjustments

## Test File Locations
| File | Type |
|---|---|
| `tests/Feature/Inventory/StockAdjustmentTest.php` | Feature (HTTP) |
| `tests/Unit/Services/Inventory/StockAdjustmentServiceTest.php` | Unit |
| `tests/Unit/Enums/AdjustmentReasonTest.php` | Unit (enum values) |

## Feature Test Cases

**Access control**
- User with `stock.adjust` can create an adjustment
- User without `stock.adjust` gets 403 on create
- Admin can view all adjustments
- Sales Rep can view only their own adjustments (list scoped to `user_id`)
- Sales Rep gets 403 accessing another user's adjustment detail

**Validation**
- Missing `reason` returns 422
- Invalid `reason` value returns 422
- `quantity_change = 0` returns 422
- Missing `product_variant_id` or `store_id` returns 422
- Non-existent `batch_id` returns 422

**Adjustment creation — positive delta**
- Batch `remaining_quantity` increases by `quantity_change`
- `StockAdjustment` record created with correct attributes
- Activity log entry created with correct user and reason

**Adjustment creation — negative delta**
- Batch `remaining_quantity` decreases by `abs(quantity_change)`
- If result = 0: batch `status` becomes `closed`
- If delta would make `remaining_quantity < 0`: returns 422 with clear error message

**Batch auto-selection**
- When `batch_id` omitted: oldest active batch selected
- When no active batch exists and delta is negative: returns 422
- When no active batch exists and delta is positive: correction batch created

**Filters**
- Filter by `store_id`, `reason`, `date_from`/`date_to` returns correct results

## Unit Test Cases — `StockAdjustmentService`
- `apply()` with explicit `batch_id` uses that batch
- `apply()` without `batch_id` auto-selects oldest active batch
- `validateDelta()` throws `InsufficientStockException` when result < 0
- Transaction rolled back on failure (batch unchanged, no record created)

## Coverage Goals
- [ ] Both positive and negative delta scenarios covered
- [ ] Auto-batch selection logic fully tested
- [ ] Authorization scoping for Sales Rep verified
- [ ] Activity log entries asserted in feature tests
- [ ] Boundary: exact zero delta rejected
