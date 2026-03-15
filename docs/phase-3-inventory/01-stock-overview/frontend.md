# Task 01 — Frontend: Stock Overview

## Pages
| File | Route | Description |
|---|---|---|
| `resources/js/Pages/Inventory/StockOverview/Index.vue` | `/inventory/stock` | Main stock table with filters |
| `resources/js/Pages/Inventory/StockOverview/Show.vue` | `/inventory/stock/{variant}` | Per-variant store breakdown |

## Components
| File | Description |
|---|---|
| `resources/js/Components/Inventory/StockFilters.vue` | Filter bar (store, category, brand, low-stock toggle) |
| `resources/js/Components/Inventory/LowStockBadge.vue` | Red badge shown when `low_stock = true` |
| `resources/js/Components/Inventory/StoreStockBreakdown.vue` | Per-store quantity chips/table |

## PrimeVue Components Used
| PrimeVue Component | Usage |
|---|---|
| `DataTable` + `Column` | Main stock table with lazy pagination |
| `Dropdown` | Store / category / brand filter selects |
| `InputSwitch` | Low-stock toggle filter |
| `Tag` | Low-stock badge (severity="danger") |
| `IconField` + `InputText` | Search input |
| `Skeleton` | Loading state for table rows |

## Key Patterns

**Inertia page props:**
```js
// Index.vue receives:
const props = defineProps({
  stocks: Object,      // paginated LengthAwarePaginator
  stores: Array,
  categories: Array,
  brands: Array,
  filters: Object,     // current active filters
})
```

**Reactive filters with Inertia router:**
```js
const filters = reactive({ store_id: null, low_stock: false, search: '' })
watch(filters, () => router.get(route('inventory.stock.index'), filters, { preserveState: true }))
```

**Per-store breakdown in table:**
- Each row expands (PrimeVue `row expansion`) to show `StoreStockBreakdown`
- Or: per-store columns rendered dynamically based on `stores` prop

## State Management
- No Pinia store needed — filters are URL-driven via Inertia
- `usePage().props` for shared auth/store context

## Responsiveness
- On mobile: collapse per-store columns, show only global stock + low-stock badge
- Use PrimeVue `DataTable` `responsiveLayout="scroll"`
