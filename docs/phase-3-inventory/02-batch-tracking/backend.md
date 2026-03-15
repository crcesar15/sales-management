# Task 02 — Backend: Batch Tracking

## Key Implementation Steps
1. Add `store()` relation and update `$fillable` on `Batch` model (if not done in Task 01)
2. Add `getExpiryStatusAttribute()` accessor to `Batch` model
3. Create `BatchController` with `index`, `show`, `close` actions
4. Create `BatchService` with FIFO selection and lifecycle transition methods
5. Create `BatchFilters` (query scope or filter class) for reusable filtering
6. Add route group under `/inventory/batches` with permission middleware on `close`
7. Configure Spatie activity log on `Batch` model for `close` events

## Key Classes / Files
| File | Purpose |
|---|---|
| `app/Models/Batch.php` | Model with accessor, scopes, relationships |
| `app/Http/Controllers/Inventory/BatchController.php` | index, show, close |
| `app/Services/Inventory/BatchService.php` | FIFO logic, lifecycle transitions |
| `app/Http/Requests/Inventory/CloseBatchRequest.php` | Validation + authorization |
| `app/Http/Resources/BatchResource.php` | Response shaping |

## Batch Model — Key Methods
```php
// Accessor: 'ok' | 'expiring_soon' | 'expired' | null
public function getExpiryStatusAttribute(): ?string

// Scope: batches available for FIFO consumption
public function scopeAvailable(Builder $query, int $variantId, int $storeId): Builder

// Scope: expiring within threshold days
public function scopeExpiringSoon(Builder $query, int $days): Builder
```

## BatchService — Key Methods
```php
// Selects the correct batch(es) to deduct from (FIFO)
public function deductFIFO(int $variantId, int $storeId, int $quantity): void

// Closes a batch, logs the action
public function closeBatch(Batch $batch, ?string $notes, User $actor): void

// Transitions queued → active on first deduction
public function activateIfQueued(Batch $batch): void
```

## Important Patterns
- Use Spatie `LogsActivity` trait on `Batch`; log `close` action with `causedBy($user)`
- Wrap `deductFIFO` in a DB transaction — batch may span multiple rows
- `expiry_alert_days` fetched from `settings` table via a `Settings` service/helper

## Packages
- `spatie/laravel-activitylog` — log close events
- `spatie/laravel-permission` — `stock.adjust` gate on `close`

## Gotchas
- Never modify `initial_quantity` after creation
- FIFO must account for `remaining_quantity > 0` (a batch can be `active` with 0 remaining in race conditions)
- Locking: use `lockForUpdate()` inside transaction during deduction to prevent overselling
