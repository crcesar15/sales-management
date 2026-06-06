# Frontend — Sales Orders

## Pages to Create
| Page | Path | Description |
|---|---|---|
| `SalesOrders/Index` | `resources/js/Pages/SalesOrders/Index.vue` | Filterable, paginated order list |
| `SalesOrders/Show/Index` | `resources/js/Pages/SalesOrders/Show/Index.vue` | Order detail with items, payments, and status actions |
| `SalesOrders/Create/Index` | `resources/js/Pages/SalesOrders/Create/Index.vue` | Manual order creation form |
| `SalesOrders/Edit/Index` | `resources/js/Pages/SalesOrders/Edit/Index.vue` | Edit draft order |

## Components to Create
| Component | Purpose |
|---|---|
| `SalesOrders/OrderStatusBadge.vue` | Colored badge per status |
| `SalesOrders/OrderItemsTable.vue` | Line items display |
| `SalesOrders/OrderTotalsCard.vue` | Subtotal / discount / tax / total |
| `SalesOrders/OrderPaymentsTable.vue` | Payment methods and amounts |
| `SalesOrders/StatusTransitionButtons.vue` | Contextual action buttons per status |
| `SalesOrders/CustomerSelect.vue` | AutoComplete for customer lookup (shared with POS) |

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
| `InputNumber` | Item quantity, discount value |
| `AutoComplete` | Customer search |

## Status Badge Colors
| Status | Tag Severity |
|---|---|
| `draft` | `secondary` |
| `sent` | `info` |
| `paid` | `success` |
| `held` | `warning` |
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

**Visibility Scope**
- If `canViewAll` prop is `true`, show all orders in the store
- If `false`, filter to `user_id = auth()->id()` only
- The backend enforces this scope regardless of frontend display

## Order Detail Layout
```
┌─────────────────────────────────────────────────────────────────┐
│ Order #88                                    [Status: Paid ✓]  │
│ Customer: Jane Doe    Cashier: John Sales   Store: Main Store  │
│ Shift: Register 1 (opened 08:00)           Token: 550e8400... │
├─────────────────────────────────────────────────────────────────┤
│ Items                                                           │
│ ┌─────────────────────────────────────────────────────────────┐ │
│ │ Product          Sale Unit  Qty  Unit Price  Line Total   │ │
│ │ Apple Juice      Bottle     3x    $25.00      $75.00      │ │
│ │ Banana Smoothie  (base)     2x    $12.50      $25.00      │ │
│ └─────────────────────────────────────────────────────────────┘ │
│                                                                 │
│ ┌──────────────────┐  ┌──────────────────────────────────────┐  │
│ │   Totals         │  │   Payments                          │  │
│ │ Subtotal: $150   │  │ Cash          $94.45                │  │
│ │ Discount: -$15   │  │ Credit Card   $50.00  ****4242     │  │
│ │ Tax (7%): $9.45  │  │ ─────────────────────               │  │
│ │ Total: $144.45   │  │ Total:        $144.45               │  │
│ └──────────────────┘  └──────────────────────────────────────┘  │
│                                                                 │
│ [Mark as Sent] [Mark as Paid] [Cancel Order]                   │
└─────────────────────────────────────────────────────────────────┘
```

## Order List — Held Orders Tab
The list page includes a tab or filter for `held` orders, making it easy to see parked orders. Clicking a held order shows the detail page with a "Resume" button that links to the POS with the order loaded.

## Notes
- Order list shows "Walk-in" when `customer` is null
- Index shows own orders only unless `canViewAll` prop is true
- Create page (manual order) reuses `CustomerSelect` component from POS
- The `payments` section on the detail page shows each payment method and amount
- `held` status is displayed with a warning badge and is filterable on the index page
- `cash_register_shift_id` is displayed on the detail page for POS-created orders, null for manual orders