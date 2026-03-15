# Phase 6 — Task 04: Purchasing Reports — Frontend

## Pages

| Page | Path | Description |
|---|---|---|
| `Reports/Purchasing/Index.vue` | `/reports/purchasing` | Tabbed purchasing report with 3 sections |

## Components to Create

| Component | Location | Purpose |
|---|---|---|
| `PoSummaryMetrics.vue` | `Components/Reports/Purchasing/` | 3 metric chips: spend, pending, lead time |
| `PoSummaryTable.vue` | `Components/Reports/Purchasing/` | Paginated PO list with status badges |
| `PoSummaryFilters.vue` | `Components/Reports/Purchasing/` | Vendor, status, date range filters |
| `VendorSpendTable.vue` | `Components/Reports/Purchasing/` | Vendor aggregation table |
| `ReceptionAccuracyTable.vue` | `Components/Reports/Purchasing/` | Line-item variance table |
| `ReceptionAccuracyFilters.vue` | `Components/Reports/Purchasing/` | Vendor, date range, variance type |
| `VarianceBadge.vue` | `Components/Shared/` | Coloured indicator for over/under/exact |

## PrimeVue Components Used

| PrimeVue Component | Usage |
|---|---|
| `TabView` / `Tabs` + `TabPanel` | Three-section tab navigation |
| `DataTable` + `Column` | PO list, vendor spend, reception accuracy |
| `Select` (Dropdown) | Vendor filter, status filter, variance type |
| `DatePicker` | Date range filters |
| `Tag` | PO status badge, variance type badge |
| `Card` | Metric summary (spend, pending count, lead time) |
| `Skeleton` | Per-tab deferred loading |
| `Message` | Empty state / no data |
| `ProgressBar` | Optional — variance percentage visualisation |

## Key Patterns

**Variance badge styling**
```js
const varianceClass = (variance) => {
  if (variance > 0) return 'text-green-600'  // over delivery
  if (variance < 0) return 'text-red-600'    // under delivery
  return 'text-gray-500'                      // exact
}
```

**Reception accuracy — variance column**
```vue
<Column field="variance" header="Variance">
  <template #body="{ data }">
    <span :class="varianceClass(data.variance)">
      {{ data.variance > 0 ? '+' : '' }}{{ data.variance }}
      ({{ data.variance_pct }}%)
    </span>
  </template>
</Column>
```

**Lead time display — null guard**
```vue
<span>{{ metrics.avg_lead_time_days != null ? `${metrics.avg_lead_time_days} days` : 'N/A' }}</span>
```

**Vendor Spend — sorted table**
```vue
<!-- DataTable with default sort by total spend desc -->
<DataTable :value="vendorSpend" :sort-field="'total_ordered_value'" :sort-order="-1">
```

## Notes
- All three tabs are deferred — each shows `Skeleton` until its data prop is resolved
- Variance type filter has 4 options: All, Over, Under, Exact
- Vendor spend has no pagination for v1 (reasonable vendor count assumed)
- Admin guard: this page should not appear in the nav for non-Admin users; backend enforces 403
