# Task 03: Catalog — Frontend

## Pages (Inertia + Vue 3)

| File                                    | Route         | Description                              |
|-----------------------------------------|---------------|------------------------------------------|
| `Pages/Catalog/Index.vue`               | `/catalog`    | Product-centric catalog with vendor comparison |

> No create or edit pages — those redirect to vendor-scoped routes:
> - Create: `route('vendors.catalog.create', vendorId)`
> - Edit: `route('vendors.catalog.edit', [vendorId, catalogId])`
> - Delete: `route('vendors.catalog.destroy', [vendorId, catalogId])`

## Components

| File                                          | Purpose                                      |
|-----------------------------------------------|----------------------------------------------|
| `Components/Catalog/VendorComparisonRow.vue` | Expandable row showing vendor offerings per variant |
| `Components/Catalog/CatalogStatusTag.vue`     | Status tag for active/inactive entries      |

## PrimeVue Components Used

| Component      | Usage                                          |
|----------------|------------------------------------------------|
| `DataTable`    | Main catalog listing with expandable rows      |
| `Column`       | Product name, variant, vendor count, actions   |
| `Tag`          | Status display (active/inactive)               |
| `Button`       | Add vendor, edit, delete actions               |
| `Select`       | Status filter, vendor filter                   |
| `InputText`    | Search/filter by product name                  |

## Key Patterns

**Expandable rows** — group catalog entries by product variant; expand to show all vendor offerings:

```vue
<DataTable v-model:expandedRows="expandedRows" :rows="10">
  <Column expander />
  <Column field="product_name" header="Product" :sortable="true" />
  <Column field="variant_name" header="Variant" />
  <Column field="vendor_count" header="Vendors" />
  <Column header="Best Price">
    <template #body="{ data }">
      {{ formatCurrency(data.lowest_price) }}
    </template>
  </Column>
  <template #expansion="{ data }">
    <VendorComparisonRow :entries="data.catalog_entries" />
  </template>
</DataTable>
```

**Grouping on the frontend** — group the flat paginated list by `product_variant_id`:

```typescript
const groupedEntries = computed(() => {
  const groups = new Map<number, {
    product_name: string
    variant_name: string
    catalog_entries: CatalogResponse[]
    lowest_price: number
  }>()

  for (const entry of props.catalogEntries.data) {
    const key = entry.product_variant_id
    if (!groups.has(key)) {
      groups.set(key, {
        product_name: entry.product_variant?.product?.name ?? '',
        variant_name: entry.product_variant?.name ?? '',
        catalog_entries: [],
        lowest_price: Infinity,
      })
    }
    const group = groups.get(key)!
    group.catalog_entries.push(entry)
    if (entry.price < group.lowest_price) {
      group.lowest_price = entry.price
    }
  }

  return Array.from(groups.values())
})
```

**Action redirects** — create/edit/delete navigate to vendor-scoped routes:

```typescript
function addVendor(entry: CatalogResponse) {
  router.visit(route('vendors.catalog.create', entry.vendor_id))
}

function editEntry(entry: CatalogResponse) {
  router.visit(route('vendors.catalog.edit', [entry.vendor_id, entry.id]))
}
```

**Filtering** — server-side via Inertia router visits:

```typescript
router.visit(route('catalog'), {
  data: { filter: searchQuery.value, status: statusFilter.value },
  preserveState: true,
  replace: true,
})
```

## Legacy Pages to Remove

- `Pages/Catalog/Index.vue` — old Options API component using `api.variants` route
- `Pages/Catalog/Edit/Index.vue` — old Options API component managing vendor-variant pivot

These are replaced by the new `Pages/Catalog/Index.vue` (product-centric) and the existing vendor-scoped pages under `Pages/Vendors/Catalog/`.

## Notes
- The product-centric view and vendor-scoped view show the same data; this page groups by variant, the vendor page groups by vendor
- Show "Best Price" column that highlights the lowest vendor price per variant
- Highlight the best price in the expanded vendor comparison (lowest price row gets a visual indicator)