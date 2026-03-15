# Task 03: Purchase Orders — Frontend

## Pages (Inertia + Vue 3)

| File                                       | Route                         | Description                       |
|--------------------------------------------|-------------------------------|-----------------------------------|
| `Pages/PurchaseOrders/Index.vue`           | `/purchase-orders`            | List with filters and status tags |
| `Pages/PurchaseOrders/Create.vue`          | `/purchase-orders/create`     | Multi-step PO builder             |
| `Pages/PurchaseOrders/Edit.vue`            | `/purchase-orders/{id}/edit`  | Edit draft PO                     |
| `Pages/PurchaseOrders/Show.vue`            | `/purchase-orders/{id}`       | Detail view with action buttons   |

## Components

| File                                                | Purpose                                      |
|-----------------------------------------------------|----------------------------------------------|
| `Components/PurchaseOrders/POLineItemsTable.vue`    | Editable line items grid                     |
| `Components/PurchaseOrders/POStatusBadge.vue`       | Colored status chip                          |
| `Components/PurchaseOrders/POTotalsPanel.vue`       | Sub-total, discount, total display           |
| `Components/PurchaseOrders/POActionButtons.vue`     | Contextual action buttons per status         |

## PrimeVue Components Used

| Component       | Usage                                          |
|-----------------|------------------------------------------------|
| `DataTable`     | PO list and line items editor                  |
| `Select`        | Vendor selector, variant selector              |
| `InputNumber`   | Quantity, discount inputs                      |
| `DatePicker`    | order_date, expected_arrival_date              |
| `Textarea`      | Notes                                          |
| `Button`        | Submit, approve, cancel, send, pay             |
| `ConfirmDialog` | Confirm cancellation                           |
| `Tag`           | Status badges                                  |
| `Stepper`       | Visual status progress indicator               |

## Key Patterns

**Vendor-scoped variant selection:**
```js
// When vendor changes, reload available catalog entries
watch(() => form.vendor_id, async (vendorId) => {
  catalogItems.value = await fetchActiveCatalog(vendorId)
})
```

**Line items** — selecting a variant auto-populates the `price` from the catalog entry (displayed as read-only, stored as snapshot). Quantity is the only editable field per line.

**POActionButtons** — renders different buttons based on `status`:
- `draft` → "Submit for Approval" + "Cancel"
- `awaiting_approval` → "Approve" (requires permission) + "Cancel"
- `approved` → "Mark as Sent" + "Cancel"
- `sent` → "Mark as Paid"
- `paid` / `cancelled` → no actions

## Notes
- Edit is only available when status is `draft`
- Show/hide action buttons based on user permissions (passed via Inertia shared props)
