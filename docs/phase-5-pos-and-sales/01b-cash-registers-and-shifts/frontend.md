# Frontend — Cash Registers & Shifts

## Pages to Create
| Page | Path | Description |
|---|---|---|
| `CashRegisters/Index` | `resources/js/Pages/CashRegisters/Index.vue` | Register list with status filter, store filter |
| `CashRegisters/Create/Index` | `resources/js/Pages/CashRegisters/Create/Index.vue` | Create register form |
| `CashRegisters/Edit/Index` | `resources/js/Pages/CashRegisters/Edit/Index.vue` | Edit register form |
| `CashRegisterShifts/Index` | `resources/js/Pages/CashRegisterShifts/Index.vue` | Shift history list with filters |
| `CashRegisterShifts/Show/Index` | `resources/js/Pages/CashRegisterShifts/Show/Index.vue` | Shift detail with movements and totals |

## Components to Create
| Component | Purpose |
|---|---|
| `CashRegisters/RegisterForm.vue` | Shared form fields for Create/Edit (name, code, status, is_default) |
| `CashRegisterShifts/OpenShiftDialog.vue` | Modal dialog: select register, enter opening balance, optional notes |
| `CashRegisterShifts/CloseShiftDialog.vue` | Modal dialog: enter closing balance, optional notes, shows expected vs actual |
| `CashRegisterMovements/MovementForm.vue` | Cash in/out form: type selector, amount, reason |

## PrimeVue Components Used
| PrimeVue Component | Usage |
|---|---|
| `DataTable` + `Column` | Register list, shift list, movements list |
| `InputText` | Name, code, reason fields |
| `InputNumber` | Opening balance, closing balance, movement amount |
| `Select` | Register status filter, shift status filter, movement type |
| `Button` | Open shift, close shift, add movement, actions |
| `Dialog` | Open/close shift modals |
| `Tag` | Shift status badge (open=success, closed=info, forced_close=danger) |
| `Toast` | Success/error feedback |
| `ConfirmDialog` | Deactivation and deletion confirmation |

## TypeScript Types
File: `resources/js/Types/cash-register-types.ts`

```typescript
export interface CashRegister {
  id: number
  store_id: number
  name: string
  code: string
  status: 'active' | 'inactive'
  is_default: boolean
  created_at: string | null
  updated_at: string | null
}

export interface CashRegisterResponse extends CashRegister {
  store: { id: number; name: string; code: string }
  current_shift: CashRegisterShiftResponse | null
}

export interface CashRegisterShift {
  id: number
  cash_register_id: number
  user_id: number
  status: 'open' | 'closed' | 'forced_close'
  opening_balance: number
  closing_balance: number | null
  expected_closing: number | null
  difference: number | null
  opened_at: string | null
  closed_at: string | null
  notes: string | null
}

export interface CashRegisterShiftResponse extends CashRegisterShift {
  cash_register: { id: number; name: string; code: string }
  user: { id: number; full_name: string }
  movements: CashRegisterMovementResponse[]
}

export interface CashRegisterMovement {
  id: number
  cash_register_shift_id: number
  user_id: number
  type: 'cash_in' | 'cash_out'
  amount: number
  reason: string
}

export interface CashRegisterMovementResponse extends CashRegisterMovement {
  user: { id: number; full_name: string }
}

export interface OpenShiftPayload {
  cash_register_id: number
  opening_balance: number
  notes?: string | null
}

export interface CloseShiftPayload {
  closing_balance: number
  notes?: string | null
}

export interface MovementPayload {
  cash_register_shift_id: number
  type: 'cash_in' | 'cash_out'
  amount: number
  reason: string
}
```

## Composable
File: `resources/js/Composables/useCashRegisterClient.ts`

Wraps `useApi()` for typed API calls:
- `listRegisters(storeId, filters)` → GET `/api/v1/cash-registers`
- `getRegister(id)` → GET `/api/v1/cash-registers/{id}`
- `getOpenShift(registerId)` → GET `/api/v1/cash-registers/{id}/open-shift`
- `addMovement(shiftId, payload)` → POST `/api/v1/shifts/{id}/movements`

Web routes use Inertia `router.post()` / `router.patch()` for mutations (open shift, close shift).

## Key Patterns

**Shift Open Dialog**
```vue
<Dialog v-model:visible="showOpenDialog" header="Open Shift" modal>
  <div class="flex flex-col gap-4">
    <Select v-model="form.register_id" :options="activeRegisters" optionLabel="name" optionValue="id" />
    <InputNumber v-model="form.opening_balance" mode="currency" :minFractionDigits="2" />
    <InputText v-model="form.notes" placeholder="Optional notes" />
  </div>
  <template #footer>
    <Button label="Open Shift" @click="submitOpen" />
  </template>
</Dialog>
```

**Shift Close — Expected vs Actual Display**
```vue
<div v-if="shift" class="grid grid-cols-2 gap-4">
  <div>
    <span class="font-semibold">Expected:</span>
    <span>{{ formatCurrency(shift.expected_closing) }}</span>
  </div>
  <div>
    <span class="font-semibold">Actual:</span>
    <InputNumber v-model="form.closing_balance" mode="currency" />
  </div>
  <div v-if="shift.difference !== null" class="col-span-2">
    <Tag :severity="shift.difference >= 0 ? 'success' : 'danger'">
      Difference: {{ formatCurrency(shift.difference) }}
    </Tag>
  </div>
</div>
```

**Status Badge Colors**
| Status | Tag Severity |
|---|---|
| `active` / `open` | `success` |
| `inactive` | `secondary` |
| `closed` | `info` |
| `forced_close` | `danger` |

## Menu Placement
Add to `resources/js/Layouts/Composables/useMenuItems.ts`:

```typescript
{
  key: "cash-registers",
  label: t("Cash Registers"),
  icon: "fa fa-cash-register",
  to: "cash-registers",
  can: "cash_register.view",
},
{
  key: "shifts",
  label: t("Shifts"),
  icon: "fa fa-clock",
  to: "shifts",
  can: "shift.view",
},
```

These entries should be placed under a "POS" menu group, alongside the POS entry and Sales Orders.

## Notes
- Register list is scoped to the user's store (multi-store users see only their assigned store)
- Shift list shows the cashier's name, register, status, opening/closing balance, and difference
- The "Open Shift" button is only available when a register has no open shift
- Closing a shift should only be available to the user who opened it (enforced on backend)
- Force-close is available to users with `shift.manage` permission