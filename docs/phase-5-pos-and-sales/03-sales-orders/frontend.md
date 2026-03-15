# Frontend — Sales Orders

## Pages to Create
| Page | Path | Description |
|---|---|---|
| `SalesOrders/Index` | `Pages/SalesOrders/Index.vue` | Filterable, paginated order list |
| `SalesOrders/Show` | `Pages/SalesOrders/Show.vue` | Order detail with items and status actions |
| `SalesOrders/Create` | `Pages/SalesOrders/Create.vue` | Manual order creation form |

## Components to Create
| Component | Purpose |
|---|---|
| `SalesOrders/OrderStatusBadge.vue` | Colored badge per status |
| `SalesOrders/OrderItemsTable.vue` | Line items display |
| `SalesOrders/OrderTotalsCard.vue` | Subtotal / discount / tax / total |
| `SalesOrders/StatusTransitionButtons.vue` | Contextual action buttons per status |

## PrimeVue Components Used
| PrimeVue | Usage |
|---|---|
| `DataTable` + `Column` | Orders list |
| `Tag` | Order status badge |
| `Select` | Status filter, payment method filter |
| `Calendar` (DatePicker) | Date range filter |
| `Button` | Status transitions, cancel |
| `ConfirmDialog` | Confirm cancellation |
| `Panel` | Order detail sections |
| `Timeline` | Status history (optional) |

## Status Badge Colors
| Status | Severity |
|---|---|
| `draft` | `secondary` |
| `sent` | `info` |
| `paid` | `success` |
| `cancelled` | `danger` |

## Key Patterns

**Filters with Inertia**
```js
watchDebounced(filters, (val) => {
  router.get('/sales-orders', val, { preserveState: true, replace: true })
}, { debounce: 350 })
```

**Status Transition**
```js
// StatusTransitionButtons.vue — emits action, parent calls router.patch
router.patch(`/sales-orders/${order.id}/status`, { status: 'paid' })
```

**Permission-Gated UI**
```vue
<Button v-if="$page.props.auth.permissions.includes('sales.manage')"
  label="Mark as Paid" @click="transition('paid')" />
```

## Notes
- Order list shows "Walk-in" when `customer` is null
- Index shows own orders only unless `canViewAll` prop is true
- Create page (manual order) reuses `CustomerSelect` component from POS
