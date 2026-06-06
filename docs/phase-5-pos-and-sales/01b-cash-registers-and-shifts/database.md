# Database — Cash Registers & Shifts

## Table: `cash_registers`

| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `store_id` | `foreignId` | FK → `stores.id` cascadeOnDelete |
| `name` | `string(100)` | Display name (e.g. "Register 1", "Front Desk") |
| `code` | `string(20)` | Short code for quick identification |
| `status` | `enum('active','inactive')` | default 'active' |
| `is_default` | `boolean` | default false — one per store |
| `created_at` / `updated_at` | `timestamps` | |

**Unique constraint:** `(store_id, code)` — register codes are unique within a store.

**Indexes:**
| Index | Column(s) | Reason |
|---|---|---|
| Unique | `store_id, code` | Register code uniqueness per store |
| Index | `status` | Filter active registers quickly |

## Table: `cash_register_shifts`

| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `cash_register_id` | `foreignId` | FK → `cash_registers.id` cascadeOnDelete |
| `user_id` | `foreignId` | FK → `users.id` — the cashier who opened the shift |
| `status` | `enum('open','closed','forced_close')` | default 'open' |
| `opening_balance` | `decimal(12,2)` | default 0 — cash counted at shift start |
| `closing_balance` | `decimal(12,2)` nullable | Cash counted at shift end |
| `expected_closing` | `decimal(12,2)` nullable | System-calculated: opening + cash_sales + cash_in − cash_out |
| `difference` | `decimal(12,2)` nullable | `closing_balance − expected_closing` (positive = over, negative = short) |
| `opened_at` | `timestamp` nullable | When the shift was opened |
| `closed_at` | `timestamp` nullable | When the shift was closed |
| `notes` | `text` nullable | Optional notes at close |
| `created_at` / `updated_at` | `timestamps` | |

**Indexes:**
| Index | Column(s) | Reason |
|---|---|---|
| Index | `cash_register_id, status` | Find open shift for a register quickly |
| Index | `user_id` | Filter shifts by cashier |

**Constraint:** Only one shift with `status = 'open'` per `cash_register_id`. Enforced at the application level in `CashRegisterShiftService::openShift()` — query for existing open shift and abort if found.

## Table: `cash_register_movements`

| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `cash_register_shift_id` | `foreignId` | FK → `cash_register_shifts.id` cascadeOnDelete |
| `user_id` | `foreignId` | FK → `users.id` — who recorded the movement |
| `type` | `enum('cash_in','cash_out')` | Direction of the movement |
| `amount` | `decimal(12,2)` | Amount moved |
| `reason` | `string(255)` | Why (e.g. "Petty cash replenishment", "Cash drop for safe") |
| `created_at` / `updated_at` | `timestamps` | |

**Indexes:**
| Index | Column(s) | Reason |
|---|---|---|
| Index | `cash_register_shift_id` | List movements for a shift |
| Index | `type` | Filter by cash_in vs cash_out |

## Relationships
```
CashRegister belongsTo Store
CashRegister hasMany CashRegisterShift
CashRegister hasOne currentShift (where status = 'open')
CashRegisterShift belongsTo CashRegister
CashRegisterShift belongsTo User (cashier)
CashRegisterShift hasMany CashRegisterMovement
CashRegisterShift hasMany SalesOrder (via cash_register_shift_id on sales_orders)
CashRegisterMovement belongsTo CashRegisterShift
CashRegisterMovement belongsTo User
```

## Migration Notes
- Create three new migrations: `create_cash_registers_table`, `create_cash_register_shifts_table`, `create_cash_register_movements_table`
- The `cash_register_shift_id` column on `sales_orders` is added in a separate migration as part of Task 02 (Sales Orders)
- `is_default` enforcement: at the application level, when setting `is_default = true`, clear `is_default` on all other registers for the same store first
- The `opening_balance` and `closing_balance` use `decimal(12,2)` for currency precision — consistent with the `sales_orders` columns

## Expected Closing Calculation
The `expected_closing` value on shift close is computed as:

```
expected_closing = opening_balance
                 + SUM(sales_order_payments.amount WHERE payment_method = 'cash' AND shift = this shift)
                 + SUM(cash_register_movements.amount WHERE type = 'cash_in' AND shift = this shift)
                 - SUM(cash_register_movements.amount WHERE type = 'cash_out' AND shift = this shift)
```

`difference = closing_balance - expected_closing`

- A positive difference means the drawer is over (more cash than expected)
- A negative difference means the drawer is short
- Zero difference means the count matches exactly