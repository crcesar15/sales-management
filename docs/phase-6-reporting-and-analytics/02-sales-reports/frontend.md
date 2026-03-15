# Phase 6 — Task 02: Sales Reports — Frontend

## Pages

| Page | Path | Description |
|---|---|---|
| `Reports/Sales/Index.vue` | `/reports/sales` | Full sales report: filters, metrics, table |

## Components to Create

| Component | Location | Purpose |
|---|---|---|
| `SalesReportFilters.vue` | `Components/Reports/Sales/` | All filter controls, emits filter object |
| `SalesMetricsBar.vue` | `Components/Reports/Sales/` | 6 KPI summary chips/cards |
| `SalesOrdersTable.vue` | `Components/Reports/Sales/` | Paginated order breakdown table |
| `OrderStatusBadge.vue` | `Components/Shared/` | Coloured tag for `paid`/`refunded`/`cancelled` |

## PrimeVue Components Used

| PrimeVue Component | Usage |
|---|---|
| `DataTable` + `Column` | Paginated orders breakdown table |
| `Select` (Dropdown) | Store, user, payment method, status filters |
| `DatePicker` | Date range (selectionMode="range") |
| `AutoComplete` | Product/variant search filter |
| `Tag` | Order status badge |
| `Card` | Metric summary cards |
| `Paginator` | Table pagination (or built-in DataTable pagination) |
| `Skeleton` | Loading state while filters reload |
| `Message` | Empty state / no results |

## Key Patterns

**Filters → URL sync with Inertia**
```js
// SalesReportFilters.vue — push filters to URL without full reload
function applyFilters() {
  router.get(route('reports.sales'), filters.value, {
    preserveState: true,
    replace: true,
    only: ['metrics', 'orders'],
  })
}
```

**Initialise filters from URL (shared state)**
```js
const props = defineProps({ filters: Object, metrics: Object, orders: Object })
const filters = reactive({ ...props.filters })
```

**Refund row styling**
```vue
<Column field="total" header="Total">
  <template #body="{ data }">
    <span :class="data.status === 'refunded' ? 'text-red-600' : ''">
      {{ formatCurrency(data.total) }}
    </span>
  </template>
</Column>
```

**Role-based filter visibility**
```vue
<!-- Only show store/user filters to view_all users -->
<template v-if="$page.props.auth.can['reports.view_all']">
  <Select v-model="filters.store_id" :options="stores" />
  <Select v-model="filters.user_id" :options="users" />
</template>
```

## Notes
- Preserve scroll position on filter change: `preserveScroll: true`
- DataTable `lazy` mode not required — pagination handled server-side via Inertia
- `SalesMetricsBar` shows skeleton while `only: ['metrics']` partial reload is in-flight
- `formatCurrency` helper in `composables/useFormatters.ts`
