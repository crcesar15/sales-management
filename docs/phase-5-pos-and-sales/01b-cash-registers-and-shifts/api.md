# API — Cash Registers & Shifts

## Web Routes (Inertia)
All routes registered in `routes/web.php` under the `auth` middleware group.

| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/cash-registers` | Register list page | `cash_register.view` |
| `GET` | `/cash-registers/create` | Create register form | `cash_register.create` |
| `POST` | `/cash-registers` | Store new register | `cash_register.create` |
| `GET` | `/cash-registers/{cashRegister}/edit` | Edit register form | `cash_register.edit` |
| `PUT` | `/cash-registers/{cashRegister}` | Update register | `cash_register.edit` |
| `DELETE` | `/cash-registers/{cashRegister}` | Delete register | `cash_register.delete` |
| `GET` | `/shifts` | Shift list page | `shift.view` |
| `POST` | `/shifts/open` | Open a shift | `shift.open` |
| `PATCH` | `/shifts/{shift}/close` | Close a shift | `shift.close` |
| `PATCH` | `/shifts/{shift}/force-close` | Force-close a shift | `shift.manage` |
| `GET` | `/shifts/{shift}` | Shift detail page | `shift.view` |

## API Routes (JSON)
All routes registered in `routes/api.php` under `v1` prefix with `auth:sanctum` middleware.

| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/api/v1/cash-registers` | List registers for user's store | `cash_register.view` |
| `GET` | `/api/v1/cash-registers/{id}` | Register detail | `cash_register.view` |
| `GET` | `/api/v1/cash-registers/{id}/open-shift` | Get current open shift for register | `sales.create` |
| `POST` | `/api/v1/shifts/{id}/movements` | Add cash in/out movement | `cash_movement.create` |

## API Route Naming
- Web routes: `cash-registers`, `cash-registers.create`, `cash-registers.store`, `cash-registers.edit`, `cash-registers.update`, `cash-registers.destroy`, `shifts`, `shifts.open`, `shifts.close`, `shifts.force-close`, `shifts.show`
- API routes: `api.v1.cash-registers`, `api.v1.cash-registers.show`, `api.v1.cash-registers.open-shift`, `api.v1.shifts.movements.store`

## Inertia Page Props (Register List)
```json
{
  "registers": {
    "data": [
      {
        "id": 1,
        "name": "Register 1",
        "code": "REG-01",
        "status": "active",
        "is_default": true,
        "store": { "id": 1, "name": "Main Store", "code": "MAIN" },
        "current_shift": null
      }
    ],
    "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 }
  },
  "filters": { "status": "active" }
}
```

## Inertia Page Props (Shift List)
```json
{
  "shifts": {
    "data": [
      {
        "id": 1,
        "status": "open",
        "opening_balance": 500.00,
        "closing_balance": null,
        "expected_closing": null,
        "difference": null,
        "opened_at": "2026-05-31T08:00:00Z",
        "closed_at": null,
        "cash_register": { "id": 1, "name": "Register 1", "code": "REG-01" },
        "user": { "id": 2, "full_name": "Jane Sales" },
        "movements": []
      }
    ],
    "meta": { ... }
  },
  "filters": { "status": "open", "register_id": null }
}
```

## Shift Detail Props
```json
{
  "shift": {
    "id": 1,
    "status": "closed",
    "opening_balance": 500.00,
    "closing_balance": 1250.00,
    "expected_closing": 1230.00,
    "difference": 20.00,
    "opened_at": "2026-05-31T08:00:00Z",
    "closed_at": "2026-05-31T17:00:00Z",
    "notes": "End of day count",
    "cash_register": { "id": 1, "name": "Register 1", "code": "REG-01" },
    "user": { "id": 2, "full_name": "Jane Sales" },
    "movements": [
      { "id": 1, "type": "cash_in", "amount": 100.00, "reason": "Petty cash replenishment", "created_at": "..." },
      { "id": 2, "type": "cash_out", "amount": 50.00, "reason": "Office supplies", "created_at": "..." }
    ]
  }
}
```

## Validation Rules (Create Register)
| Field | Rules |
|---|---|
| `store_id` | `required\|exists:stores,id` |
| `name` | `required\|string\|max:100` |
| `code` | `required\|string\|max:20\|unique:cash_registers,code,NULL,id,store_id,{store_id}` |
| `status` | `sometimes\|in:active,inactive` |
| `is_default` | `sometimes\|boolean` |

## Validation Rules (Open Shift)
| Field | Rules |
|---|---|
| `cash_register_id` | `required\|exists:cash_registers,id` |
| `opening_balance` | `required\|numeric\|min:0` |
| `notes` | `nullable\|string` |

## Validation Rules (Close Shift)
| Field | Rules |
|---|---|
| `closing_balance` | `required\|numeric\|min:0` |
| `notes` | `nullable\|string` |

## Validation Rules (Add Movement)
| Field | Rules |
|---|---|
| `cash_register_shift_id` | `required\|exists:cash_register_shifts,id` |
| `type` | `required\|in:cash_in,cash_out` |
| `amount` | `required\|numeric\|min:0.01` |
| `reason` | `required\|string\|max:255` |

## Error Responses
- 422: Attempting to open a shift on a register that already has an open shift
- 422: Attempting to close a shift that is not open
- 422: Attempting to delete a register that has existing shifts
- 422: Attempting to add a movement to a closed shift
- 403: User without `shift.manage` attempting to force-close
- 403: User attempting to close a shift they did not open (unless they have `shift.manage`)