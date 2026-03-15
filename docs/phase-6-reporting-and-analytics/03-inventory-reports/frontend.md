# Phase 6 — Task 03: Inventory Reports — Frontend

## Pages

| Page | Path | Description |
|---|---|---|
| `Reports/Inventory/Index.vue` | `/reports/inventory` | Tabbed inventory report with 3 sections |

## Components to Create

| Component | Location | Purpose |
|---|---|---|
| `StockLevelsTable.vue` | `Components/Reports/Inventory/` | Paginated variant stock table |
| `BatchStatusTable.vue` | `Components/Reports/Inventory/` | Paginated batch expiry table |
| `StockMovementTable.vue` | `Components/Reports/Inventory/` | Paginated unified movement history |
| `StockLevelFilters.vue` | `Components/Reports/Inventory/` | Store, category, brand, low-stock filters |
| `BatchFilters.vue` | `Components/Reports/Inventory/` | Store, expiry range, status filters |
| `MovementFilters.vue` | `Components/Reports/Inventory/` | Variant, store, date range, type filters |
| `MovementTypeBadge.vue` | `Components/Shared/` | Coloured tag for movement type |

## PrimeVue Components Used

| PrimeVue Component | Usage |
|---|---|
| `TabView` / `Tabs` + `TabPanel` | Three-section tab navigation |
| `DataTable` + `Column` | All three data tables |
| `Select` (Dropdown) | Store, category, brand, status, type filters |
| `DatePicker` | Expiry range and movement date range |
| `AutoComplete` | Variant search in movement filter |
| `ToggleButton` | Low-stock filter toggle |
| `Tag` | Status and movement type badges |
| `Skeleton` | Per-tab deferred loading state |
| `Message` | Empty state per section |

## Key Patterns

**Tabbed layout with independent filter state**
```js
// Index.vue
const activeTab = ref(0)
const stockFilters = reactive({ store_id: null, category_id: null, low_stock: false })
const batchFilters = reactive({ store_id: null, expiry_from: null, expiry_to: null, status: null })
const movFilters   = reactive({ store_id: null, variant_id: null, type: null })
```

**Per-section partial reload**
```js
function applyStockFilters() {
  router.reload({
    data: { ...stockFilters },
    only: ['stockLevels'],
    preserveScroll: true,
    replace: true,
  })
}
```

**Movement type badge colours**
```js
const typeColour = {
  sale: 'danger', adjustment: 'warning',
  transfer_in: 'success', transfer_out: 'info', reception: 'secondary'
}
```

**Low-stock row highlight**
```vue
<DataTable :row-class="row => row.is_low_stock ? 'bg-red-50' : ''">
```

## Notes
- Each tab section independently handles its own filters and pagination
- Deferred props mean each tab shows a `Skeleton` until data loads
- Avoid loading all 3 sections simultaneously — defer non-active tabs
- Section filter state is NOT persisted to URL (complexity vs value tradeoff for v1)
