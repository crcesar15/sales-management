# Task 03 — Frontend: Stock Transfers

## Pages
| File | Route | Description |
|---|---|---|
| `resources/js/Pages/Inventory/Transfers/Index.vue` | `/inventory/transfers` | Transfer list |
| `resources/js/Pages/Inventory/Transfers/Create.vue` | `/inventory/transfers/create` | New transfer form |
| `resources/js/Pages/Inventory/Transfers/Show.vue` | `/inventory/transfers/{id}` | Detail + status actions |

## Components
| File | Description |
|---|---|
| `resources/js/Components/Inventory/TransferStatusStepper.vue` | Horizontal step indicator |
| `resources/js/Components/Inventory/TransferItemsTable.vue` | Editable items grid (qty columns) |
| `resources/js/Components/Inventory/AdvanceTransferModal.vue` | Confirm status + enter quantities |
| `resources/js/Components/Inventory/TransferStatusTag.vue` | Colored status badge |

## PrimeVue Components Used
| PrimeVue Component | Usage |
|---|---|
| `Steps` | Workflow status stepper |
| `DataTable` + `Column` | Transfer list + items breakdown |
| `Dialog` | Advance status / cancel confirmation |
| `InputNumber` | `quantity_sent` / `quantity_received` inputs |
| `Dropdown` | Store selector in create form |
| `AutoComplete` | Product variant search |
| `Tag` | Status badges |
| `Button` | Actions (advance, cancel) |
| `Toast` | Success/error feedback |

## Key Patterns

**Create form (useForm):**
```js
const form = useForm({
  from_store_id: null,
  to_store_id: null,
  notes: '',
  items: [],   // [{ product_variant_id, quantity_requested }]
})
```

**Dynamic item rows:**
- User searches for a variant (AutoComplete), adds it to `form.items`
- Inline InputNumber for `quantity_requested` per row
- Remove item row button

**Advance status modal:**
- Show current → next status
- When advancing to `in_transit`: show InputNumber per item for `quantity_sent`
- When advancing to `received`: show InputNumber per item for `quantity_received`

**Discrepancy highlight:**
```js
const discrepancy = (item) => item.quantity_sent - item.quantity_received
// Show in red if discrepancy > 0
```

## Authorization
- "Create Transfer" button hidden unless user has `stock.adjust`
- "Advance Status" / "Cancel" buttons conditioned on same permission
- Sales Rep sees only transfers where `from_store_id` or `to_store_id` is their store
