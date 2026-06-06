# Task 01b — Cash Registers & Shifts

## Prerequisites
- No prerequisites — this task is standalone, building on existing `stores` and `users` tables

## What
Cash register and shift management for the POS. Registers represent physical tills in a store. Shifts track when a cashier opens a register, how much cash starts in the drawer, and all movements during the shift until close.

## Why
A cashier-facing POS needs accountability for cash handling. Without register and shift tracking, there is no way to know who is operating a till, whether the drawer was counted correctly at open, or how much cash should be present at close. Shifts enforce that only one cashier operates a register at a time and that sales can only be processed when a register is open.

## Requirements
- Manage cash registers per store (name, code, active/inactive status, default flag)
- Open a shift: select a register, enter opening balance, link to the cashier (user)
- Only one open shift per register at a time — opening a second shift on the same register is blocked
- Close a shift: enter closing balance, system calculates the expected amount (opening balance + cash sales + cash in − cash out), displays the difference
- Cash in/out movements during a shift (type, amount, reason)
- Forced close with manager permission for abandoned shifts
- POS checkout requires an open shift — the `cash_register_shift_id` is recorded on every sales order
- Default register: one register per store can be marked `is_default` for quick POS startup

## Acceptance Criteria
- [ ] Cash registers can be created, edited, and deactivated via the management UI
- [ ] A shift can be opened on an active register with an opening balance
- [ ] Attempting to open a second shift on the same register returns a validation error
- [ ] Cash in/out movements can be recorded on an open shift
- [ ] Closing a shift calculates expected closing balance correctly: `opening_balance + sum(cash payments) + sum(cash_in movements) − sum(cash_out movements)`
- [ ] The difference between expected and actual closing balance is stored and displayed
- [ ] A manager with `shift.manage` permission can force-close a shift
- [ ] Deleting a register that has existing shifts is blocked
- [ ] POS checkout validates that an open shift exists for the authenticated user
- [ ] All mutations are logged via Spatie Activity Log

## Permissions
| Permission | Scope |
|---|---|
| `cash_register.view` | View register list and details |
| `cash_register.create` | Create a new register |
| `cash_register.edit` | Edit register name, code, status |
| `cash_register.delete` | Delete a register (blocked if shifts exist) |
| `shift.view` | View shift list and details |
| `shift.open` | Open a new shift |
| `shift.close` | Close own shift |
| `shift.manage` | Force-close any shift, view all shifts |
| `cash_movement.create` | Record cash in/out movements |

## Dependencies
- `stores` table — registers belong to a store
- `users` table — shifts are assigned to a cashier
- `spatie/laravel-permission` — permission gates
- `spatie/laravel-activitylog` — audit trail for shift events

## Notes
- The expected closing calculation references `sales_order_payments` where `payment_method = 'cash'`. Since `sales_order_payments` is created in Task 02 (Sales Orders), the shift service should calculate this as: `opening_balance + (cash_in movements) − (cash_out movements)` initially, and extend with cash sales once that table exists.
- `is_default` ensures one register per store is pre-selected in the POS. Setting a new default clears the previous default for that store.
- A register can be deactivated (status = inactive) but not deleted if it has shifts. Deactivation prevents new shifts from being opened.