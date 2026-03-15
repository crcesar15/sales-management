# Task 02 — Frontend: Batch Tracking

## Pages
| File | Route | Description |
|---|---|---|
| `resources/js/Pages/Inventory/Batches/Index.vue` | `/inventory/batches` | Batch list with filters |
| `resources/js/Pages/Inventory/Batches/Show.vue` | `/inventory/batches/{batch}` | Batch detail view |

## Components
| File | Description |
|---|---|
| `resources/js/Components/Inventory/BatchStatusTag.vue` | Status badge (queued/active/closed) |
| `resources/js/Components/Inventory/ExpiryBadge.vue` | Expiry status chip (ok/expiring/expired) |
| `resources/js/Components/Inventory/BatchQuantityBar.vue` | Visual breakdown (sold/remaining/missing) |
| `resources/js/Components/Inventory/CloseBatchModal.vue` | Confirm + notes before manual close |

## PrimeVue Components Used
| PrimeVue Component | Usage |
|---|---|
| `DataTable` + `Column` | Batch list with lazy pagination |
| `Tag` | Status and expiry badges |
| `Calendar` | Expiry date range filter |
| `Dialog` | Close batch confirmation modal |
| `ProgressBar` | Visual quantity breakdown |
| `Dropdown` | Status / store / product filters |
| `Textarea` | Notes input in close modal |
| `Button` | Close action (danger variant) |

## Key Patterns

**Page props:**
```js
defineProps({
  batches: Object,    // paginated
  stores: Array,
  filters: Object,
})
```

**Expiry status color mapping:**
```js
const expiryColors = {
  ok: 'success',
  expiring_soon: 'warning',
  expired: 'danger',
}
```

**Close batch flow:**
1. User clicks "Close Batch" button (visible only if `can('stock.adjust')`)
2. `CloseBatchModal` opens with optional notes field
3. On confirm: `router.patch(route('batches.close', batch.id), { notes })`
4. On success: Inertia reloads page props

## Authorization in Template
```html
<Button v-if="$page.props.can['stock.adjust']" label="Close Batch" severity="danger" />
```

## Notes
- `Show.vue` links back to the Reception Order for traceability
- Quantity breakdown bar: sold (blue) + remaining (green) + missing (red) = initial
