# Task 02 — Testing: Batch Tracking

## Test File Locations
| File | Type |
|---|---|
| `tests/Feature/Inventory/BatchTrackingTest.php` | Feature (HTTP) |
| `tests/Unit/Services/Inventory/BatchServiceTest.php` | Unit |
| `tests/Unit/Models/BatchTest.php` | Unit (model accessors) |

## Feature Test Cases

**Access control**
- Admin can view batch list and detail
- Sales Rep cannot access batch detail (returns 403)
- Non-authenticated user is redirected to login
- Only user with `stock.adjust` can close a batch

**Batch list filters**
- Filter by `status` returns only matching batches
- Filter by `store_id` returns batches for that store
- Filter by `product_variant_id` returns correct batches
- Filter `expiring_soon=true` returns batches within threshold days
- Filter by expiry date range works correctly

**Close batch**
- Admin with `stock.adjust` can close an `active` batch
- Closing a `queued` batch is allowed
- Closing an already `closed` batch returns 422
- Close action is recorded in activity log with correct causer and notes

## Unit Test Cases — `BatchService`
- `deductFIFO()` selects oldest available batch first
- `deductFIFO()` spans multiple batches when one batch has insufficient quantity
- `deductFIFO()` throws exception when total available stock is insufficient
- `activateIfQueued()` sets status to `active` and saves
- `closeBatch()` sets `status = closed` and logs activity

## Unit Test Cases — `Batch` Model
- `expiry_status` accessor returns `null` when `expiry_date` is null
- Returns `expired` when `expiry_date` is in the past
- Returns `expiring_soon` when within `expiry_alert_days`
- Returns `ok` when expiry is beyond threshold

## Coverage Goals
- [ ] FIFO logic fully covered, including multi-batch deduction
- [ ] All lifecycle transitions tested
- [ ] Activity log entries verified
- [ ] Expiry accessor covers all four states
