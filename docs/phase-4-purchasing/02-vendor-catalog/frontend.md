# Task 02: Vendor Catalog — Frontend

## Pages (Inertia + Vue 3)

| File                                          | Route                              | Description                     |
|-----------------------------------------------|------------------------------------|---------------------------------|
| `Pages/Vendors/Catalog/Index.vue`             | `/vendors/{id}/catalog`            | List catalog entries for vendor |
| `Pages/Vendors/Catalog/Create.vue`            | `/vendors/{id}/catalog/create`     | Add new catalog entry           |
| `Pages/Vendors/Catalog/Edit.vue`              | `/vendors/{id}/catalog/{entry}/edit` | Edit catalog entry             |

## Components

| File                                          | Purpose                                      |
|-----------------------------------------------|----------------------------------------------|
| `Components/Catalog/CatalogEntryForm.vue`     | Shared form for create/edit                  |
| `Components/Catalog/CatalogStatusToggle.vue`  | Quick active/inactive toggle in list         |

## PrimeVue Components Used

| Component      | Usage                                          |
|----------------|------------------------------------------------|
| `DataTable`    | Catalog entries list                           |
| `Column`       | Variant name, price, unit, MOQ, lead time      |
| `InputNumber`  | price, conversion_factor, MOQ, lead_time_days  |
| `Select`       | Product variant selector, purchase unit, status |
| `ToggleSwitch` | Quick status toggle (active/inactive)          |
| `Button`       | Edit, delete, add entry                        |
| `Tag`          | Status display                                 |

## Key Patterns

**Variant selector** — filtered to variants not already in this vendor's catalog (prevent duplicates); uses `Select` with `filter` prop for search.

**Conversion factor helper text** — display computed label:
```js
// "1 Box = 12 units"
const unitLabel = computed(() =>
  purchaseUnit.value ? `1 ${purchaseUnit.value.name} = ${form.conversion_factor} base units` : ''
)
```

**Status toggle** — inline toggle in the DataTable row sends a PATCH to update status without leaving the page.

## Notes
- Page is accessed from the Vendor detail/edit page (tab or sub-route)
- Show `minimum_order_quantity` and `lead_time_days` as optional fields (collapsible section or clearly marked optional)
- `conversion_factor` input should be disabled / forced to 1 when no `purchase_unit_id` is selected
