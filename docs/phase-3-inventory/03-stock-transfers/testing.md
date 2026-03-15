# Task 03 — Testing: Stock Transfers

## Test File Locations
| File | Type |
|---|---|
| `tests/Feature/Inventory/StockTransferTest.php` | Feature (HTTP) |
| `tests/Unit/Services/Inventory/StockTransferServiceTest.php` | Unit |

## Feature Test Cases

**Access control**
- User with `stock.adjust` can create a transfer
- User without permission gets 403 on create/status update
- Sales Rep without permission can only view (GET) transfers for their store

**Validation**
- `from_store_id = to_store_id` returns 422
- Missing `items` returns 422
- `quantity_requested <= 0` returns 422
- Non-existent `product_variant_id` returns 422

**Transfer creation**
- Transfer created with status `requested`
- Items saved with correct `quantity_requested`
- Activity log entry created

**Status transitions**
- `requested` → `picked`: succeeds, activity logged
- `picked` → `in_transit`: updates `quantity_sent` on items
- `in_transit` → `received`: updates `quantity_received`
- `received` → `completed`: deducts source batches, creates destination batch
- Illegal transition (e.g., `requested` → `completed`) returns 422
- Cancellation from `requested`, `picked`, `in_transit` succeeds
- Cancellation from `completed` returns 422

**Completion logic**
- Source store batches are deducted FIFO by `quantity_received`
- New batch created at destination store with correct quantities
- Discrepancy is correctly reflected in `quantity_sent vs quantity_received`
- Insufficient source stock prevents completion (transaction rolled back)

## Unit Test Cases — `StockTransferService`
- `createTransfer()` returns `StockTransfer` with correct attributes
- `transitionStatus()` throws on invalid state transition
- `completeTransfer()` deducts batches and creates destination batch
- `completeTransfer()` rolls back on failure (no partial state)

## Coverage Goals
- [ ] All status transitions covered (valid + invalid)
- [ ] FIFO deduction verified at completion
- [ ] Discrepancy recording tested
- [ ] Authorization tested for all endpoints
- [ ] DB transaction rollback tested on failure
