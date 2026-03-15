# Task 04: Reception Orders — Frontend

## Pages (Inertia + Vue 3)

| File                                          | Route                          | Description                         |
|-----------------------------------------------|--------------------------------|-------------------------------------|
| `Pages/ReceptionOrders/Index.vue`             | `/reception-orders`            | List all receptions                 |
| `Pages/ReceptionOrders/Create.vue`            | `/reception-orders/create`     | Create reception against a PO       |
| `Pages/ReceptionOrders/Show.vue`              | `/reception-orders/{id}`       | Detail view with complete/cancel    |
| `Pages/PurchaseOrders/Show.vue`               | (existing) add receptions tab  | Show linked receptions on PO detail |

## Components

| File                                                 | Purpose                                      |
|------------------------------------------------------|----------------------------------------------|
| `Components/ReceptionOrders/ReceptionLineItems.vue`  | Editable line items with expiry date         |
| `Components/ReceptionOrders/StoreSelector.vue`       | Destination store selector                   |
| `Components/ReceptionOrders/ReceptionStatusBadge.vue`| Status chip                                  |

## PrimeVue Components Used

| Component       | Usage                                               |
|-----------------|-----------------------------------------------------|
| `Select`        | PO selector, store selector                         |
| `DataTable`     | Line items editor and list view                     |
| `InputNumber`   | Quantity per line item                              |
| `DatePicker`    | reception_date, expiry_date per item                |
| `Textarea`      | Notes                                               |
| `Button`        | Complete, Cancel actions                            |
| `ConfirmDialog` | Confirm completion (irreversible stock update)      |
| `Tag`           | Status badge                                        |
| `Toast`         | Success/error feedback after completion             |

## Key Patterns

**PO selector** — filters to POs with status `approved` or `sent`:
```js
const eligiblePOs = computed(() =>
  purchaseOrders.value.filter(po => ['approved', 'sent'].includes(po.status))
)
```

**Expiry date per line item** — `ReceptionLineItems` table includes a `DatePicker` column for optional expiry. Only shown if the variant is batch-tracked.

**Completion confirmation** — warn user that completing will update stock and create batches; action is irreversible:
```js
confirm.require({ message: 'Stock will be updated and batches created. Continue?', ... })
```

## Notes
- The `store_id` field is required and prominently displayed at the top of the form
- On create, pre-populate line items from PO line items as starting quantities (user can adjust)
- Show conversion factor info per line item: "5 boxes × 12 = 60 units will be added to stock"
