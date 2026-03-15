# Task 04 — Frontend: Stock Adjustments

## Pages
| File | Route | Description |
|---|---|---|
| `resources/js/Pages/Inventory/Adjustments/Index.vue` | `/inventory/adjustments` | Adjustment list with filters |
| `resources/js/Pages/Inventory/Adjustments/Create.vue` | `/inventory/adjustments/create` | Create adjustment form |
| `resources/js/Pages/Inventory/Adjustments/Show.vue` | `/inventory/adjustments/{id}` | Read-only detail view |

## Components
| File | Description |
|---|---|
| `resources/js/Components/Inventory/AdjustmentReasonTag.vue` | Colored tag per reason type |
| `resources/js/Components/Inventory/QuantityChangeDisplay.vue` | +N (green) / -N (red) display |
| `resources/js/Components/Inventory/BatchSelector.vue` | Optional batch picker dropdown |

## PrimeVue Components Used
| PrimeVue Component | Usage |
|---|---|
| `DataTable` + `Column` | Adjustment list |
| `Dropdown` | Reason selector, store filter |
| `AutoComplete` | Product variant search |
| `InputNumber` | `quantity_change` input (allow negative) |
| `Textarea` | Notes input |
| `Tag` | Reason type display |
| `Calendar` | Date range filter |
| `Toast` | Success/error notifications |
| `Message` | Warning if no active batch found |

## Key Patterns

**Create form:**
```js
const form = useForm({
  product_variant_id: null,
  store_id: null,
  batch_id: null,          // optional
  quantity_change: 0,
  reason: null,
  notes: '',
})
```

**Quantity change display — color coding:**
```js
const isNegative = (val) => val < 0
// Apply text-red-500 for negative, text-green-600 for positive
```

**Reason dropdown options** (from `AdjustmentReason` enum, passed as page prop):
```js
defineProps({ reasons: Array }) // [{ value: 'damage', label: 'Damage' }, ...]
```

**Scoped list for Sales Rep:**
- If `auth.user.role === 'sales_rep'`, hide "User" column and `user_id` filter
- Backend enforces scope; frontend merely hides irrelevant UI

## Validation (client-side)
- `quantity_change` cannot be `0` (warn the user)
- `reason` is required
- `product_variant_id` and `store_id` required before submitting

## Notes
- Adjustments are read-only after creation — no Edit button on `Show.vue`
- Link to associated batch from `Show.vue` (if `batch_id` is not null)
