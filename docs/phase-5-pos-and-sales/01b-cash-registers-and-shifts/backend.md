# Backend — Cash Registers & Shifts

## Implementation Steps
1. Create migrations for `cash_registers`, `cash_register_shifts`, `cash_register_movements`
2. Create enums: `CashRegisterStatus`, `ShiftStatus`, `MovementType`
3. Create Eloquent models: `CashRegister`, `CashRegisterShift`, `CashRegisterMovement` — all `final`, with `LogsActivity`
4. Create `CashRegisterService` — CRUD, `is_default` enforcement, deletion guard
5. Create `CashRegisterShiftService` — open/close/forceClose/addMovement, expected closing calculation
6. Create web controllers: `CashRegisterController`, `CashRegisterShiftController`
7. Create form requests: `StoreCashRegisterRequest`, `UpdateCashRegisterRequest`, `OpenShiftRequest`, `CloseShiftRequest`, `ForceCloseShiftRequest`, `StoreMovementRequest`
8. Create Inertia resources: `CashRegisterResource`, `CashRegisterShiftResource`, `CashRegisterMovementResource`
9. Register permissions in `PermissionsEnum` and `PermissionSeeder`
10. Add menu entries in `useMenuItems.ts`
11. After Task 02 (Sales Orders) is complete: add `salesOrders()` HasMany relationship to `CashRegisterShift` model and update `CashRegisterShiftService::closeShift()` to include cash sales from `sales_order_payments` in the `expected_closing` calculation

> **Note**: API controllers are not needed for this task. The management pages use Inertia (web controllers with redirects). POS-specific API endpoints for shift management (open shift, close shift, get session info, list registers) are defined in Task 03 (POS Interface) under the `api.v1.pos.*` route group.

## Key Files
```
app/Enums/CashRegisterStatus.php
app/Enums/CashRegisterShiftStatus.php
app/Enums/CashMovementType.php
app/Models/CashRegister.php
app/Models/CashRegisterShift.php
app/Models/CashRegisterMovement.php
app/Services/CashRegisterService.php
app/Services/CashRegisterShiftService.php
app/Http/Controllers/CashRegisterController.php
app/Http/Controllers/CashRegisterShiftController.php
app/Http/Requests/CashRegisters/StoreCashRegisterRequest.php
app/Http/Requests/CashRegisters/UpdateCashRegisterRequest.php
app/Http/Requests/CashRegisterShifts/OpenShiftRequest.php
app/Http/Requests/CashRegisterShifts/CloseShiftRequest.php
app/Http/Requests/CashRegisterShifts/ForceCloseShiftRequest.php
app/Http/Requests/CashRegisterMovements/StoreMovementRequest.php
app/Http/Resources/CashRegister/CashRegisterResource.php
app/Http/Resources/CashRegister/CashRegisterCollection.php
app/Http/Resources/CashRegisterShift/CashRegisterShiftResource.php
app/Http/Resources/CashRegisterShift/CashRegisterShiftCollection.php
app/Http/Resources/CashRegisterMovement/CashRegisterMovementResource.php
database/migrations/xxxx_create_cash_registers_table.php
database/migrations/xxxx_create_cash_register_shifts_table.php
database/migrations/xxxx_create_cash_register_movements_table.php
```

## Enums

**`CashRegisterStatus`**: `ACTIVE = 'active'`, `INACTIVE = 'inactive'`

**`CashRegisterShiftStatus`**: `OPEN = 'open'`, `CLOSED = 'closed'`, `FORCED_CLOSE = 'forced_close'`

**`CashMovementType`**: `CASH_IN = 'cash_in'`, `CASH_OUT = 'cash_out'`

## Model Patterns

**`CashRegister`** — `final class`, uses `HasFactory`, `LogsActivity`:
- `$fillable`: `store_id`, `name`, `code`, `status`, `is_default`
- `$casts`: `status` → `CashRegisterStatus::class`, `is_default` → `boolean`
- Relationships: `store()` BelongsTo, `shifts()` HasMany, `currentShift()` HasOne (where status = open)
- `getActivitylogOptions()`: log fillable, log only dirty, log name `cash_register`

**`CashRegisterShift`** — `final class`, uses `HasFactory`, `LogsActivity`:
- `$fillable`: `cash_register_id`, `user_id`, `status`, `opening_balance`, `closing_balance`, `expected_closing`, `difference`, `opened_at`, `closed_at`, `notes`
- `$casts`: `status` → `CashRegisterShiftStatus::class`, `opening_balance` → `decimal:2`, `closing_balance` → `decimal:2`, `expected_closing` → `decimal:2`, `difference` → `decimal:2`, `opened_at` → `datetime`, `closed_at` → `datetime`
- Relationships: `register()` BelongsTo, `cashier()` BelongsTo (User), `movements()` HasMany. (Note: `salesOrders()` HasMany will be added in Task 02 when the `SalesOrder` model is created)
- Scope: `scopeOpen($query)` → where status = open

**`CashRegisterMovement`** — `final class`, uses `HasFactory`, `LogsActivity`:
- `$fillable`: `cash_register_shift_id`, `user_id`, `type`, `amount`, `reason`
- `$casts`: `type` → `CashMovementType::class`, `amount` → `decimal:2`
- Relationships: `shift()` BelongsTo, `user()` BelongsTo

## Service Patterns

**`CashRegisterService`** — `final class`, constructor injection:
- `list(?int $storeId = null, string $status = 'all', int $perPage = 20, ?string $filter = null): LengthAwarePaginator` — filter by store (nullable; when null, returns all stores), status, name/code search; eager loads `store` + `currentShift`
- `create(array $data): CashRegister` — DB::transaction, if `is_default`, clear other defaults for same store, activity log
- `update(CashRegister $register, array $data): CashRegister` — if setting `is_default`, clear others for store, activity log
- `delete(CashRegister $register): void` — guard: throw `InvalidArgumentException` if register has any shifts (even closed), DB::transaction, activity log

**`CashRegisterShiftService`** — `final class`, constructor injection:
- `list(array $filters, int $perPage): LengthAwarePaginator` — filter by register, user, status, date range
- `openShift(CashRegister $register, User $cashier, float $openingBalance, ?string $notes = null): CashRegisterShift`
  - Guard: register must be `active`
  - Guard: no open shift exists for this register → throw `InvalidArgumentException`
  - Create shift with `status = open`, `opened_at = now()`, `opening_balance = $openingBalance`
  - Activity log: "Shift opened on register {name}"
- `closeShift(CashRegisterShift $shift, float $closingBalance, ?string $notes = null): CashRegisterShift`
  - Guard: shift must be `open` → throw `InvalidArgumentException`
  - Calculate `expected_closing` (see formula in database.md)
  - Calculate `difference = closing_balance - expected_closing`
  - Set `status = closed`, `closing_balance`, `expected_closing`, `difference`, `closed_at = now()`
  - Activity log: "Shift closed. Difference: {difference}"
- `forceCloseShift(CashRegisterShift $shift, User $manager, float $closingBalance, ?string $notes = null): CashRegisterShift`
  - Guard: shift must be `open` → throw `InvalidArgumentException`
  - Same logic as `closeShift` but sets `status = forced_close`
  - Activity log: "Shift force-closed by {manager->name}"
- `addMovement(CashRegisterShift $shift, string $type, float $amount, string $reason, User $user): CashRegisterMovement`
  - Guard: shift must be `open` → throw `InvalidArgumentException`
  - Create movement, activity log

## Expected Closing Calculation
```php
$expectedClosing = $shift->opening_balance
    + $shift->salesOrders()
        ->whereHas('payments', fn($q) => $q->where('payment_method', 'cash'))
        ->sum('amount')  // Note: this joins through sales_orders_payments
    + $shift->movements()->where('type', 'cash_in')->sum('amount')
    - $shift->movements()->where('type', 'cash_out')->sum('amount');
```

Since `sales_order_payments` does not exist yet (created in Task 02), the initial implementation calculates:
```php
$expectedClosing = $shift->opening_balance
    + $shift->movements()->where('type', 'cash_in')->sum('amount')
    - $shift->movements()->where('type', 'cash_out')->sum('amount');
```
And adds the cash sales component once Task 02 is complete.

## Form Request Validation

**`StoreCashRegisterRequest`**:
- `authorize()`: `$this->user()?->can(PermissionsEnum::CASH_REGISTERS_CREATE->value)`
- `store_id` — required|exists:stores,id
- `name` — required|string|max:100
- `code` — required|string|max:20|unique:cash_registers,code,NULL,id,store_id,{store_id}
- `status` — sometimes|in:active,inactive
- `is_default` — sometimes|boolean

**`UpdateCashRegisterRequest`**:
- Same rules, all fields optional, `code` unique rule ignores current model

**`OpenShiftRequest`**:
- `authorize()`: `$this->user()?->can(PermissionsEnum::SHIFTS_OPEN->value)`
- `cash_register_id` — required|exists:cash_registers,id
- `opening_balance` — required|numeric|min:0
- `notes` — nullable|string

**`CloseShiftRequest`**:
- `authorize()`: returns `true` (authorization handled in controller based on shift ownership)
- `closing_balance` — required|numeric|min:0
- `notes` — nullable|string

**`ForceCloseShiftRequest`**:
- `authorize()`: `$this->user()?->can(PermissionsEnum::SHIFTS_MANAGE->value)`
- `closing_balance` — required|numeric|min:0
- `notes` — nullable|string

**`StoreMovementRequest`**:
- `authorize()`: `$this->user()?->can(PermissionsEnum::CASH_MOVEMENTS_CREATE->value)`
- `type` — required|string|in:cash_in,cash_out
- `amount` — required|numeric|min:0.01
- `reason` — required|string|max:255

> **Note:** `cash_register_shift_id` is not in the request body — it comes from the route parameter `/shifts/{shift}/movements`.

## Controller Patterns

**`CashRegisterController`** (Web) — `final class`, `readonly` constructor injection of `CashRegisterService`:
- `index()` — authorize `CASH_REGISTERS_VIEW`, list registers (nullable store filter), render `CashRegisters/Index` with `registers` prop and `filters` prop
- `create()` — authorize `CASH_REGISTERS_CREATE`, render `CashRegisters/Create/Index`
- `store(StoreCashRegisterRequest)` — call service, redirect to `cash-registers`
- `edit(CashRegister)` — authorize `CASH_REGISTERS_EDIT`, load `store` + `currentShift`, render `CashRegisters/Edit/Index` with resolved `CashRegisterResource`
- `update(UpdateCashRegisterRequest, CashRegister)` — call service, redirect to `cash-registers`
- `destroy(CashRegister)` — authorize `CASH_REGISTERS_DELETE`, call service (wrapped in try/catch for `InvalidArgumentException`), redirect to `cash-registers`

**`CashRegisterShiftController`** (Web) — `final class`, `readonly` constructor injection of `CashRegisterShiftService`:
- `index()` — authorize `SHIFTS_VIEW`, list shifts (filterable by register, status, date), render `CashRegisterShifts/Index`
- `openShift(OpenShiftRequest)` — find register, call service (wrapped in try/catch for `InvalidArgumentException`), redirect to `shifts`
- `closeShift(CloseShiftRequest, CashRegisterShift)` — **authorization**: if user is not the shift opener, require `SHIFTS_MANAGE`; otherwise require `SHIFTS_CLOSE`. Call service (wrapped in try/catch), redirect to `shifts`
- `forceCloseShift(ForceCloseShiftRequest, CashRegisterShift)` — call service (wrapped in try/catch for `InvalidArgumentException`), redirect to `shifts`
- `show(CashRegisterShift)` — authorize `SHIFTS_VIEW`, load `register.store` + `cashier` + `movements.user`, render `CashRegisterShifts/Show/Index` with resolved `CashRegisterShiftResource`
- `addMovement(StoreMovementRequest, CashRegisterShift)` — authorize `CASH_MOVEMENTS_CREATE` via form request, call service (wrapped in try/catch for `InvalidArgumentException`), redirect to `shifts.show`

## Routes

```php
// Cash Register Routes
Route::get('/cash-registers', [CashRegisterController::class, 'index'])->name('cash-registers');
Route::get('/cash-registers/create', [CashRegisterController::class, 'create'])->name('cash-registers.create');
Route::post('/cash-registers', [CashRegisterController::class, 'store'])->name('cash-registers.store');
Route::get('/cash-registers/{cashRegister}/edit', [CashRegisterController::class, 'edit'])->name('cash-registers.edit');
Route::put('/cash-registers/{cashRegister}', [CashRegisterController::class, 'update'])->name('cash-registers.update');
Route::delete('/cash-registers/{cashRegister}', [CashRegisterController::class, 'destroy'])->name('cash-registers.destroy');

// Shift Routes
Route::get('/shifts', [CashRegisterShiftController::class, 'index'])->name('shifts');
Route::post('/shifts/open', [CashRegisterShiftController::class, 'openShift'])->name('shifts.open');
Route::patch('/shifts/{shift}/close', [CashRegisterShiftController::class, 'closeShift'])->name('shifts.close');
Route::patch('/shifts/{shift}/force-close', [CashRegisterShiftController::class, 'forceCloseShift'])->name('shifts.force-close');
Route::get('/shifts/{shift}', [CashRegisterShiftController::class, 'show'])->name('shifts.show');
Route::post('/shifts/{shift}/movements', [CashRegisterShiftController::class, 'addMovement'])->name('shifts.movements.store');
```

## Resource Keys

The `CashRegisterShiftResource` uses the following JSON key names (matching the api.md spec):
- `cash_register` — the register relationship (Eloquent method: `register()`)
- `user` — the cashier relationship (Eloquent method: `cashier()`)

The `CashRegisterResource` includes:
- `store` — the store relationship (via `store()` Eloquent method)
- `current_shift` — the current open shift (via `currentShift()` Eloquent method)

## Gotchas
- The `is_default` flag must be managed carefully: when setting a register as default, clear `is_default` on all other registers in the same store within the same DB transaction
- Only one open shift per register — the service must check before creating
- Closing a shift must be done by the same user who opened it, unless force-closing (manager with `shift.manage`). The controller checks `$shift->user_id !== $request->user()->id` and requires `SHIFTS_MANAGE` if not the owner.
- An inactive register cannot have new shifts opened on it — guard in `openShift`
- Deleting a register is blocked if it has any shifts (open or closed) — the shift history is audit data. The controller catches `InvalidArgumentException` from the service.
- `CashRegisterService::list()` accepts a nullable `$storeId` — when null, registers from all stores are returned. When provided, only registers for that store are shown.
- All controller mutation methods wrap service calls in try/catch for `InvalidArgumentException` and redirect back with `withErrors()` on failure.
- Detail/edit views use `->resolve()` on the resource to flatten data for Inertia props.