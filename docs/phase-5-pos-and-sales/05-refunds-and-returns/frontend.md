# Frontend — Refunds & Returns

## Pages to Create
| Page | Path | Description |
|---|---|---|
| `Refunds/Index` | `Pages/Refunds/Index.vue` | Paginated refund list with status filters |
| `Refunds/Show` | `Pages/Refunds/Show.vue` | Refund detail + status action buttons |
| `Refunds/Create` | `Pages/Refunds/Create.vue` | Create refund request for a specific order |

## Components to Create
| Component | Purpose |
|---|---|
| `Refunds/RefundStatusBadge.vue` | Colored badge per status |
| `Refunds/RefundItemsForm.vue` | Item selection + qty input for new refund |
| `Refunds/RefundItemsTable.vue` | Read-only display of refund items |
| `Refunds/StatusActionButtons.vue` | Approve / Reject / Complete buttons |

## PrimeVue Components Used
| PrimeVue | Usage |
|---|---|
| `DataTable` + `Column` | Refund list and items display |
| `Tag` | Status badge |
| `InputNumber` | Quantity returned input per item |
| `Textarea` | Reason field |
| `Button` | Submit, approve, reject, complete |
| `ConfirmDialog` | Confirm approve/reject/complete actions |
| `Toast` | Success / error messages |
| `Panel` | Refund detail sections |

## Status Badge Colors
| Status | Severity |
|---|---|
| `pending` | `warning` |
| `approved` | `info` |
| `completed` | `success` |
| `rejected` | `danger` |

## Key Patterns

**Create Refund Form (`RefundItemsForm.vue`)**
```vue
<!-- Render each returnable item from the sales order -->
<tr v-for="item in orderItems" :key="item.id">
  <td>{{ item.product_name }} × {{ item.quantity }}</td>
  <td>Max returnable: {{ item.returnable_qty }}</td>
  <td><InputNumber v-model="form.items[item.id]" :max="item.returnable_qty" :min="0" /></td>
</tr>
```

**Status Transitions (Show page)**
```js
// Only include items where user entered qty > 0
const submitRefund = () => {
  const items = Object.entries(form.items)
    .filter(([, qty]) => qty > 0)
    .map(([id, qty]) => ({ sales_order_item_id: id, quantity_returned: qty }))
  router.post(`/sales-orders/${orderId}/refunds`, { reason: form.reason, items })
}
```

## Notes
- `Create` page is accessed from `SalesOrders/Show.vue` via a "Request Return" button
- Pass `returnable_qty` per item from the backend (pre-calculated)
- Show page displays the original sales order summary alongside refund items
- Status action buttons are conditional on `$page.props.auth.permissions`
