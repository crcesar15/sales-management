# Task 05 — Frontend: Stock Alerts

## Pages
| File | Route | Description |
|---|---|---|
| `resources/js/Pages/Inventory/Alerts/Index.vue` | `/inventory/alerts` | Full alert list (tabbed) |

## Components
| File | Description |
|---|---|
| `resources/js/Components/Inventory/AlertsSummaryWidget.vue` | Dashboard widget with counts |
| `resources/js/Components/Inventory/LowStockAlertList.vue` | Low-stock alert rows |
| `resources/js/Components/Inventory/ExpiryAlertList.vue` | Expiry alert rows |
| `resources/js/Components/Inventory/AlertBadge.vue` | Count badge (used in nav/dashboard) |

## PrimeVue Components Used
| PrimeVue Component | Usage |
|---|---|
| `TabView` + `TabPanel` | Separate tabs for Low Stock / Expiry alerts |
| `DataTable` + `Column` | Alert item lists |
| `Tag` | Alert severity (warning/danger) |
| `Badge` | Alert count on dashboard widget and nav menu |
| `Message` | Empty state when no alerts |
| `ProgressBar` | Stock deficit visualization (current vs minimum) |
| `Chip` | Affected store tags |

## Key Patterns

**Dashboard widget (AlertsSummaryWidget.vue):**
```js
// Receives summary as prop from DashboardController
defineProps({
  alertsSummary: Object, // { low_stock_count, expiry_count, total }
})
```

**Alert severity logic:**
```js
// Expiry: days_remaining <= 7 → danger, 8-30 → warning
const expirySeverity = (days) => days <= 7 ? 'danger' : 'warning'

// Low stock: deficit > 50% of minimum → danger, else warning
const lowStockSeverity = (item) =>
  item.current_stock === 0 ? 'danger' : 'warning'
```

**TabView structure (Index.vue):**
```html
<TabPanel header="Low Stock (4)">
  <LowStockAlertList :alerts="alerts.low_stock" />
</TabPanel>
<TabPanel header="Expiring Soon (2)">
  <ExpiryAlertList :alerts="alerts.expiry" />
</TabPanel>
```

**Navigation badge:**
- Inject `alertsSummary.total` into sidebar nav item via shared Inertia props
- Show badge only when `total > 0`

## State Management
- No Pinia store — alerts fetched as Inertia page props on navigation
- Dashboard widget receives summary as prop (included in dashboard controller response)

## Responsiveness
- On mobile: collapse table to card layout per alert item
- Use PrimeVue `DataTable` responsive mode or manual card fallback
