# Frontend — Sale Units

## Pages & Components

```
resources/js/Pages/Products/
├── VariantShow.vue                  # Variant detail — hosts SaleUnitsPanel
└── Partials/
    └── SaleUnitsPanel.vue           # Sale unit list + create/edit dialog
```

> The Variant Show page can be a sub-page of `Products/Show` or a standalone page — either works.

## PrimeVue Components Used

| Component | Usage |
|---|---|
| `DataTable` + `Column` | Sale units list (name, conversion factor, price, status, actions) |
| `InputText` | Sale unit name field |
| `InputNumber` | `conversion_factor` (integer, min 1) and `price` (currency mode) |
| `Dropdown` | Status selector (`active` / `inactive`) |
| `Tag` | Status badge in table |
| `Button` | Create, edit, delete actions |
| `Dialog` | Create/edit modal |
| `Toast` | Feedback |

## Key Inertia Patterns

**Page props:**
```ts
defineProps<{
  product: { id: number; name: string; measurement_unit: { name: string; abbreviation: string } | null }
  variant: ProductVariantResource  // includes sale_units[]
}>()
```

**Create form:**
```ts
const form = useForm({
  name: '', conversion_factor: 1, price: 0, status: 'active'
})
form.post(route('products.variants.sale-units.store', [product.id, variant.id]), {
  onSuccess: () => { dialogVisible.value = false }
})
```

**Edit form:**
```ts
function openEdit(unit: SaleUnitResource) {
  editing.value = unit
  form.name = unit.name
  form.conversion_factor = unit.conversion_factor
  form.price = parseFloat(unit.price)
  form.status = unit.status
  dialogVisible.value = true
}
form.put(route('products.variants.sale-units.update', [product.id, variant.id, editing.value.id]))
```

## SaleUnitsPanel Layout

**Header area:**
```
Variant: TSH-R-S  |  Base unit: Piece (pc)  |  Stock: 50 pieces
[+ Add Sale Unit]
```

**Table columns:** Name | Conversion Factor | Price | Status | Actions

**Base unit row (derived, not from DB):**
```vue
<!-- Always show base unit as first static row -->
<tr class="bg-gray-50 italic text-sm">
  <td>{{ product.measurement_unit?.name ?? 'Unit' }} (base)</td>
  <td>1</td>
  <td>{{ variant.price }}</td>
  <td><Tag value="Base" severity="info" /></td>
  <td>—</td>
</tr>
```

## TypeScript Types
```ts
interface SaleUnitResource {
  id: number
  name: string
  conversion_factor: number
  price: string        // DECIMAL from API — parse with parseFloat() for InputNumber
  status: 'active' | 'inactive'
}
```

## Conversion Factor Display
Show a human-readable label in the table:
```ts
function conversionLabel(unit: SaleUnitResource): string {
  const base = props.product.measurement_unit?.abbreviation ?? 'pc'
  return `${unit.conversion_factor} ${base}`  // e.g. "6 pc"
}
```

## Gotchas
- `price` from the API is a string (DECIMAL) — convert to `Number` before binding to `InputNumber`
- `conversion_factor` must be `>= 1` — use `:min="1"` on `InputNumber` and show inline error
- The base unit row is rendered client-side from `product.measurement_unit` — it is NOT a sale unit record
- After create/update, Inertia's `onSuccess` callback fires and the page reloads with fresh props automatically (no manual state mutation needed)
- Use `form.reset()` before opening the create dialog to clear any stale data from a previous edit
