# Task 03 — Backend: Stock Transfers

## Key Implementation Steps
1. Create migrations for `stock_transfers` and `stock_transfer_items`
2. Create `StockTransfer` and `StockTransferItem` models with relationships
3. Create `StockTransferController` (index, store, show, updateStatus, destroy)
4. Create `StockTransferService` — workflow transitions + batch manipulation
5. Create form requests: `CreateTransferRequest`, `UpdateTransferStatusRequest`
6. Add activity logging on `StockTransfer` model for all status changes
7. Register routes under `/inventory/transfers`

## Key Classes / Files
| File | Purpose |
|---|---|
| `database/migrations/..._create_stock_transfers_table.php` | New table |
| `database/migrations/..._create_stock_transfer_items_table.php` | New table |
| `app/Models/StockTransfer.php` | Model + relationships |
| `app/Models/StockTransferItem.php` | Model |
| `app/Http/Controllers/Inventory/StockTransferController.php` | CRUD + status |
| `app/Services/Inventory/StockTransferService.php` | Business logic |
| `app/Http/Requests/Inventory/CreateTransferRequest.php` | Validation |
| `app/Http/Requests/Inventory/UpdateTransferStatusRequest.php` | Status + items |

## StockTransferService — Key Methods
```php
public function createTransfer(array $data, User $requestedBy): StockTransfer

// Advances status, validates transition, updates item quantities
public function transitionStatus(StockTransfer $transfer, string $newStatus, array $itemData, User $actor): void

// Runs inside DB transaction: FIFO deduct + create destination batch
public function completeTransfer(StockTransfer $transfer, User $actor): void

public function cancelTransfer(StockTransfer $transfer, User $actor): void
```

## Status Transition Map
```
requested → picked → in_transit → received → completed
         └─────────────────────────────────→ cancelled
```
Validate in service: throw `InvalidStateException` on illegal transition.

## Important Patterns
- Wrap `completeTransfer` in `DB::transaction()` — multiple batch rows modified
- Use `lockForUpdate()` on batches during FIFO deduction
- Log each transition: `activity()->on($transfer)->causedBy($actor)->log("Status changed to {$newStatus}")`
- New destination batch: `reception_order_id = null`, `status = queued`

## Gotchas
- Validate `from_store_id !== to_store_id` in form request
- Validate source store has sufficient stock before creating the transfer (or at `picked` stage)
- If completion fails mid-way (e.g., insufficient stock), transaction rolls back — do not partial-complete
