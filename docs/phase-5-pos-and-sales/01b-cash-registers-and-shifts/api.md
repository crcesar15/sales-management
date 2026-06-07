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
| `POST` | `/shifts/{shift}/movements` | Add a movement to a shift | `cash_movement.create` |

## Web Route Naming
- `cash-registers`, `cash-registers.create`, `cash-registers.store`, `cash-registers.edit`, `cash-registers.update`, `cash-registers.destroy`
- `shifts`, `shifts.open`, `shifts.close`, `shifts.force-close`, `shifts.show`, `shifts.movements.store`

## No API Routes (Inertia-Only Module)
This module uses Inertia for all management pages (CRUD, shift open/close/force-close). No API controllers are needed — the management interface uses server-side rendering with Inertia redirects.

POS-specific API endpoints for shift management (open shift, close shift, get session info, list registers) are defined in **Task 03 (POS Interface)** under the `api.v1.pos.*` route group, since they are consumed exclusively by the POS single-page interface.

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
  "filters": { "store_id": null, "status": "active" }
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
  "filters": { "status": "open", "cash_register_id": null }
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
| `type` | `required\|string\|in:cash_in,cash_out` |
| `amount` | `required\|numeric\|min:0.01` |
| `reason` | `required\|string\|max:255` |

> **Note:** The shift ID is determined by the route parameter `/shifts/{shift}/movements`, not by a request body field.

## Error Responses
- 422: Attempting to open a shift on a register that already has an open shift
- 422: Attempting to close a shift that is not open
- 422: Attempting to delete a register that has existing shifts
- 422: Attempting to add a movement to a closed shift
- 403: User without `shift.manage` attempting to force-close
- 403: User attempting to close a shift they did not open (unless they have `shift.manage`)