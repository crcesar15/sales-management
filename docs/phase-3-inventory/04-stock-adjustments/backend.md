# Task 04 — Backend: Stock Adjustments

## Key Implementation Steps
1. Create migration for `stock_adjustments` table
2. Create `StockAdjustment` model with relationships and `$fillable`
3. Create `StockAdjustmentController` (index, store, show)
4. Create `StockAdjustmentService` — batch selection, validation, apply logic
5. Create `CreateAdjustmentRequest` form request (validation + permission check)
6. Scope list query: Admin sees all, Sales Rep scoped to `user_id = auth()->id()`
7. Add Spatie activity log on `StockAdjustment` creation

## Key Classes / Files
| File | Purpose |
|---|---|
| `database/migrations/..._create_stock_adjustments_table.php` | New table |
| `app/Models/StockAdjustment.php` | Model + relationships |
| `app/Http/Controllers/Inventory/StockAdjustmentController.php` | index, store, show |
| `app/Services/Inventory/StockAdjustmentService.php` | Core logic |
| `app/Http/Requests/Inventory/CreateAdjustmentRequest.php` | Validation |
| `app/Enums/AdjustmentReason.php` | Enum: all valid reason values |

## AdjustmentReason Enum
```php
enum AdjustmentReason: string {
    case PhysicalAudit = 'physical_audit';
    case Robbery       = 'robbery';
    case Expiry        = 'expiry';
    case Damage        = 'damage';
    case Correction    = 'correction';
    case Other         = 'other';
}
```

## StockAdjustmentService — Key Methods
```php
// Selects batch (explicit or FIFO auto-select), validates, applies delta, logs
public function apply(array $data, User $actor): StockAdjustment

// Throws InsufficientStockException if result would be negative
private function validateDelta(Batch $batch, int $delta): void

// Auto-select oldest active batch for variant+store
private function resolveBatch(int $variantId, int $storeId): Batch
```

## Apply Logic (inside DB transaction)
1. Resolve target batch
2. Validate: `remaining_quantity + quantity_change >= 0`
3. Update `batch->remaining_quantity += quantity_change`
4. If `remaining_quantity === 0`: set `batch->status = closed`
5. Create `StockAdjustment` record
6. Log activity: `causedBy($actor)->withProperties(['reason', 'delta'])->log('stock_adjusted')`

## Packages
- `spatie/laravel-activitylog` — log every adjustment
- `spatie/laravel-permission` — `stock.adjust` gate

## Gotchas
- Negative adjustments below zero must be rejected, not silently clamped
- If no active batch exists and delta is positive: create a "correction" batch
- Enum validation: use `Rule::enum(AdjustmentReason::class)` in form request
- Sales Rep scoping must be enforced in the controller, not just the frontend
