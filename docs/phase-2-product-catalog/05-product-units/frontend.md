# Frontend — Product Variant Units

## Location
Units are managed from the **Inventory > Variants > Variant Detail** page, under the **Units** tab. See `docs/phase-2-product-catalog/04-inventory-variants/` for the full page structure.

This doc covers the **UnitsTab component** specifically.

## Component Location

```
resources/js/Pages/Inventory/Variants/Show/
├── Index.vue              # Variant detail page with TabView
└── Components/
    └── UnitsTab.vue       # Sale + Purchase units management
```

## PrimeVue Components Used

| Component | Usage |
|---|---|
| `TabView` + `TabPanel` | Sale units tab / Purchase units tab (nested within the Units tab) |
| `DataTable` + `Column` | Units list (name, conversion factor, price, status, actions) |
| `InputText` | Unit name field |
| `InputNumber` | `conversion_factor` (integer, min 1) and `price` (currency mode, sale only) |
| `Dropdown` | Status selector (`active` / `inactive`) |
| `Tag` | Status badge in table |
| `Button` | Create, edit, delete actions |
| `Dialog` | Create/edit modal |
| `Toast` | Feedback |

## Props (received from parent page)
```ts
// The UnitsTab receives product and variant from the Show/Index.vue page
defineProps<{
  product: { id: number; name: string; measurement_unit: { name: string; abbreviation: string } | null }
  variant: ProductVariantResource  // includes sale_units[] + purchase_units[]
}>()
```

## Create Form (sale unit)
```ts
const form = useForm({
  type: 'sale',
  name: '', conversion_factor: 1, price: 0, status: 'active', sort_order: 0
})
form.post(route('products.variants.units.store', [product.id, variant.id]), {
  onSuccess: () => { dialogVisible.value = false }
})
```

## Create Form (purchase unit — no price)
```ts
const form = useForm({
  type: 'purchase',
  name: '', conversion_factor: 1, price: null, status: 'active', sort_order: 0
})
form.post(route('products.variants.units.store', [product.id, variant.id]), {
  onSuccess: () => { dialogVisible.value = false }
})
```

## Edit Form
```ts
function openEdit(unit: VariantUnitResource) {
  editing.value = unit
  form.type = unit.type  // read-only, cannot change type
  form.name = unit.name
  form.conversion_factor = unit.conversion_factor
  form.price = unit.price ? parseFloat(unit.price) : null
  form.status = unit.status
  form.sort_order = unit.sort_order
  dialogVisible.value = true
}
form.put(route('products.variants.units.update', [product.id, variant.id, editing.value.id]))
```

## UnitsTab Layout

**Header area:**
```
Base unit: Piece (pc)  |  Stock: 50 pieces
```

**Inner tabbed layout:**
```
[ Sale Units | Purchase Units ]
[+ Add Unit]          (button label changes based on active inner tab)
```

**Sale units table columns:** Name | Conversion Factor | Price | Status | Actions

**Purchase units table columns:** Name | Conversion Factor | Status | Actions
(no Price column for purchase units)

**Base unit row (derived, not from DB) — shown in Sale Units tab only:**
```vue
<!-- Always show base unit as first static row in Sale Units tab -->
<tr class="bg-gray-50 italic text-sm">
  <td>{{ product.measurement_unit?.name ?? 'Unit' }} (base)</td>
  <td>1</td>
  <td>{{ variant.price }}</td>
  <td><Tag value="Base" severity="info" /></td>
  <td>—</td>
</tr>
```

**Create dialog — conditionally show price:**
```vue
<div v-if="form.type === 'sale'" class="field">
  <label>Price</label>
  <InputNumber v-model="form.price" mode="currency" currency="USD" :min="0" />
</div>
```

## TypeScript Types
```ts
interface VariantUnitResource {
  id: number
  type: 'sale' | 'purchase'
  name: string
  conversion_factor: number
  price: string | null  // DECIMAL from API — parse with parseFloat() for InputNumber; null for purchase
  status: 'active' | 'inactive'
  sort_order: number
}
```

## Conversion Factor Display
```ts
function conversionLabel(unit: VariantUnitResource): string {
  const base = props.product.measurement_unit?.abbreviation ?? 'pc'
  return `${unit.conversion_factor} ${base}`  // e.g. "6 pc"
}
```

## Gotchas
- `price` from the API is a string (DECIMAL) or null — convert to `Number` before binding to `InputNumber`; null for purchase units
- `conversion_factor` must be `>= 1` — use `:min="1"` on `InputNumber` and show inline error
- The base unit row is rendered client-side from `product.measurement_unit` — it is NOT a unit record
- `type` is immutable after creation — disable or hide the type selector in the edit dialog
- After create/update, use `router.reload({ only: ['variant'] })` to refresh units data without full page reload
- Use `form.reset()` before opening the create dialog to clear any stale data from a previous edit
- Purchase unit form omits the price field entirely (or shows it as disabled/null)
- When switching inner tabs, close any open dialog to avoid stale type mismatch
