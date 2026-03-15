# Phase 6 — Task 01: Dashboard — Frontend

## Pages

| Page | Path | Description |
|---|---|---|
| `Dashboard/Index.vue` | `/dashboard` | Main dashboard page with KPIs, alerts, charts |

## Components to Create

| Component | Location | Purpose |
|---|---|---|
| `KpiCard.vue` | `Components/Dashboard/` | Single metric card (label, value, icon, trend) |
| `AlertsBanner.vue` | `Components/Dashboard/` | Scrollable list of active low-stock/expiry alerts |
| `RevenueTrendChart.vue` | `Components/Dashboard/` | PrimeVue Chart — line chart, last 6 months |
| `TopProductsChart.vue` | `Components/Dashboard/` | PrimeVue Chart — horizontal bar chart |
| `StoreFilter.vue` | `Components/Dashboard/` | Dropdown — Admin only; emits `store_id` change |
| `DateRangePicker.vue` | `Components/Dashboard/` | Date range input; emits `date_from`, `date_to` |

## PrimeVue Components Used

| PrimeVue Component | Usage |
|---|---|
| `Card` | KPI card wrapper |
| `Chart` | Revenue trend (type="line") and top products (type="bar") |
| `Select` (Dropdown) | Store filter |
| `DatePicker` (Calendar) | Date range selection |
| `Message` / `InlineMessage` | Alert banner items |
| `Tag` | Alert type badge (low_stock / expiry) |
| `Skeleton` | Loading state for deferred chart data |
| `ProgressSpinner` | KPI loading indicator during poll |

## Key Patterns

**Polling with Inertia partial reload**
```js
// Dashboard/Index.vue — <script setup>
import { router } from '@inertiajs/vue3'
import { onMounted, onUnmounted } from 'vue'

let pollTimer
onMounted(() => {
  pollTimer = setInterval(() => {
    router.reload({ only: ['kpis', 'alerts'], preserveScroll: true })
  }, 60_000)
})
onUnmounted(() => clearInterval(pollTimer))
```

**Deferred chart skeleton**
```vue
<Suspense>
  <RevenueTrendChart v-if="charts" :data="charts.revenue_trend" />
  <template #fallback>
    <Skeleton height="200px" />
  </template>
</Suspense>
```

**Store filter — Admin only**
```vue
<StoreFilter v-if="$page.props.auth.isAdmin" v-model="filters.store_id" />
```

**Filter reactivity → reload**
```js
watch(filters, () => {
  router.reload({ data: filters, only: ['kpis', 'alerts'], preserveScroll: true })
}, { deep: true })
```

## Chart Data Shape (passed as props)

```js
// RevenueTrendChart expects:
// { labels: ['Oct', 'Nov', ...], datasets: [{ data: [72000, 81500, ...] }] }
// Transform from backend array inside the component using a computed
```

## Notes
- `KpiCard` accepts `loading` boolean prop to show skeleton during poll
- Role check for store filter comes from `$page.props.auth.roles` (set in `HandleInertiaRequests`)
- Charts are non-interactive for v1 — no drill-down
