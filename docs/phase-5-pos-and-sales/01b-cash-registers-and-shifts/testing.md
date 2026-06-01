# Testing — Cash Registers & Shifts

## Test File Locations
```
tests/Feature/CashRegisterTest.php
tests/Feature/CashRegisterShiftTest.php
tests/Unit/Models/CashRegisterTest.php
tests/Unit/Models/CashRegisterShiftTest.php
tests/Unit/Services/CashRegisterServiceTest.php
tests/Unit/Services/CashRegisterShiftServiceTest.php
```

## Feature Test Cases — Cash Registers

### Listing
- Authenticated user with `cash_register.view` can access register list
- Register list is scoped to the user's store
- List is paginated (20 per page)
- Can filter by status (active/inactive)

### Create
- Valid payload creates a register and redirects
- Setting `is_default = true` clears the previous default for the same store
- Setting `is_default = true` does not affect defaults in other stores
- Duplicate `code` within the same store returns validation error
- Same `code` in a different store is allowed
- User without `cash_register.create` gets 403

### Update
- Valid payload updates register fields
- Changing `is_default` to true clears previous default
- Changing `status` to `inactive` prevents new shifts from being opened (tested via shift open attempt)
- User without `cash_register.edit` gets 403

### Delete
- Register with no shifts is deleted successfully
- Register with existing shifts (even closed) returns 422 with error message
- User without `cash_register.delete` gets 403

## Feature Test Cases — Shifts

### Open Shift
- Valid payload opens a shift on an active register
- `opened_at` is set automatically
- `status` is `open`
- Attempting to open a second shift on the same register returns 422
- Attempting to open a shift on an inactive register returns 422
- User without `shift.open` gets 403

### Close Shift
- Valid payload closes the shift with `closing_balance`
- `expected_closing` is calculated correctly (opening + cash_in - cash_out)
- `difference` is computed as `closing_balance - expected_closing`
- `closed_at` is set automatically
- `status` changes to `closed`
- Attempting to close a shift that is already closed returns 422
- Attempting to close another user's shift without `shift.manage` returns 403
- User can close their own shift successfully

### Force-Close Shift
- User with `shift.manage` can force-close any shift
- `status` is set to `forced_close` (not `closed`)
- User without `shift.manage` gets 403

### Add Movement
- Valid payload adds a `cash_in` movement to an open shift
- Valid payload adds a `cash_out` movement to an open shift
- Attempting to add a movement to a closed shift returns 422
- Negative amount returns validation error
- User without `cash_movement.create` gets 403

## Unit Test Cases

### CashRegisterTest (Model)
- `store()` returns BelongsTo relationship to Store
- `shifts()` returns HasMany relationship to CashRegisterShift
- `currentShift()` returns HasOne with status = open
- Setting `is_default` scope works correctly

### CashRegisterShiftTest (Model)
- `cashRegister()` returns BelongsTo relationship
- `user()` returns BelongsTo relationship
- `movements()` returns HasMany relationship
- `salesOrders()` returns HasMany relationship
- `scopeOpen()` filters to status = open

### CashRegisterServiceTest (Service)
- `create()` with `is_default = true` clears other defaults in the same store
- `create()` with `is_default = true` does not affect defaults in other stores
- `delete()` throws when register has shifts
- `delete()` succeeds when register has no shifts

### CashRegisterShiftServiceTest (Service)
- `openShift()` creates shift with correct attributes
- `openShift()` throws when register already has an open shift
- `openShift()` throws when register is inactive
- `closeShift()` calculates expected_closing correctly (opening + cash_in - cash_out)
- `closeShift()` calculates difference correctly
- `closeShift()` throws when shift is not open
- `forceCloseShift()` sets status to `forced_close`
- `forceCloseShift()` throws when user lacks `shift.manage`
- `addMovement()` creates movement with correct type and amount
- `addMovement()` throws when shift is not open

## Coverage Goals
- 100% of controller actions covered by feature tests
- All guard conditions (double open, close closed, delete with shifts) covered
- Expected closing calculation covered with various scenarios (no sales, with sales, with movements)
- Permission gating on all endpoints