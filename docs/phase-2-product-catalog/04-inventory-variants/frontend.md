# Frontend — Inventory Variants Module

## Pages & Components

```
resources/js/Pages/Inventory/Variants/
├── Index.vue                          # Variants list (all products)
└── Show/
    ├── Index.vue                      # Variant detail page with TabView
    └── Components/
        ├── DetailsTab.vue             # Tab 1: variant details form
        ├── ImagesTab.vue              # Tab 2: image management
        └── UnitsTab.vue               # Tab 3: sale + purchase units
```

## Types
**New file:** `resources/js/Types/inventory-variant-types.ts`

```ts
export interface InventoryVariantListItem {
  id: number
  product_id: number
  product_name: string
  brand_name: string | null
  name: string               // computed from option values
  identifier: string | null
  barcode: string | null
  price: string
  stock: number
  status: string
  is_default: boolean
  values: Array<{ id: number; value: string; option_name: string }>
  images: Array<{ id: number; thumb_url: string; full_url: string }>
  created_at: string
}
```

## Variants List Page (`Index.vue`)

Uses `defineOptions({ layout: AppLayout })` with PrimeVue DataTable (lazy, server-side).

**DataTable columns:**

| Column | Source | Sortable | Notes |
|--------|--------|----------|-------|
| Image | `images[0].thumb_url` | No | 45x45 thumbnail, fallback placeholder |
| Product | `product_name` | Yes | Brand name underneath in gray |
| Variant | `name` | No | Chips for option values; "Default" Tag if `is_default` |
| Identifier | `identifier` | Yes | "---" if null |
| Price | `price` | Yes | Formatted currency |
| Stock | `stock` | Yes | Integer |
| Status | `status` | Yes | Tag with severity (success/warn/danger) |
| Actions | — | No | "View" button |

**Filters:**
- Status: `SelectButton` (All / Active / Inactive / Archived)
- Search: `InputText` with debounce (searches product name)

**Actions:**
- "View" icon button → `router.visit(route('inventory.variants.show', { product: data.product_id, variant: data.id }))`

## Variant Detail Page (`Show/Index.vue`)

**Header:**
```
[← Back]  Inventory > Variants > {Product Name} > {Variant Name}
                                Status: [Active]
```

**Props:**
```ts
defineProps<{
  product: {
    id: number
    name: string
    description: string | null
    brand: { id: number; name: string } | null
    categories: Array<{ id: number; name: string }>
    measurement_unit: { id: number; name: string; abbreviation: string } | null
    media: Array<{ id: number; thumb_url: string; full_url: string }>
  }
  variant: {
    id: number
    product_id: number
    identifier: string | null
    barcode: string | null
    price: string
    stock: number
    status: string
    is_default: boolean
    values: Array<{ id: number; value: string; option_name: string }>
    images: Array<{ id: number; thumb_url: string; full_url: string }>
    sale_units: VariantUnitResource[]
    purchase_units: VariantUnitResource[]
    created_at: string
    updated_at: string
  }
}>()
```

**TabView:** Details | Images | Units

### Tab 1: Details (`DetailsTab.vue`)
Editable form with vee-validate + yup:
- `identifier` (string, nullable, max 50)
- `barcode` (string, nullable, max 100)
- `price` (number, required, min 0)
- `stock` (integer, required, min 0)
- `status` (select: active/inactive/archived)

Submits via `router.put()` to existing `variant.update` route.

For simple product variants: shows product name as read-only context.
For multi-variant: shows option values as read-only chips.

### Tab 2: Images (`ImagesTab.vue`)
- Displays variant images with position ordering
- Image picker: shows product's media library, user selects which apply to this variant
- Uses existing `variant.images.sync` route
- Falls back to product-level images when variant has none

### Tab 3: Units (`UnitsTab.vue`)
See `docs/phase-2-product-catalog/05-product-units/frontend.md` for the full UnitsTab spec.

Inner TabView with Sale Units / Purchase Units. Sale units include price, purchase units don't.

## Product Edit Page Changes

### VariantsPanel simplification
**File:** `resources/js/Pages/Products/Edit/Components/VariantsPanel.vue`

**Remove:**
- `ManualVariantDialog` import and usage
- `EditVariantImageDialog` import and usage
- Images column from DataTable
- Edit (pen) button from Actions column

**Add:**
- "Manage" button per variant row → `router.visit(route('inventory.variants.show', { product: productId, variant: data.id }))`

**Keep:**
- Generate Variants button
- Add Variant button (simplified — creates minimal variant)
- Delete button

### Simple product link
**File:** `resources/js/Pages/Products/Edit/Index.vue`

Add to the Details card (shown when `!hasVariants`):
```vue
<div class="mt-3 pt-3 border-t border-surface-200 dark:border-surface-700">
  <Button
    label="Manage Inventory"
    icon="fa fa-warehouse"
    text
    size="small"
    @click="router.visit(route('inventory.variants.show', { product: product.id, variant: defaultVariant?.id }))"
  />
</div>
```

## Sidebar Menu Update
**File:** `resources/js/Layouts/Composables/useMenuItems.ts`

Add to Inventory group (between Stores and Stock Levels):
```typescript
{
  key: "inventory-variants",
  label: t("Variants"),
  icon: "fa fa-boxes-stacked",
  to: "inventory.variants",
  can: "inventory.view",
  routeUrl: route("inventory.variants"),
},
```

Inventory group becomes: Stores | **Variants** | Stock Levels (placeholder) | Adjustments (placeholder)

## Gotchas
- Simple product default variants must appear in the list (marked with `is_default` and "Default" tag)
- The variant detail page URL includes `{product}` for REST semantics and route model binding
- Back button behavior: use query param `?from=inventory` vs `?from=product` to navigate back correctly
- Tab state is NOT preserved on page reload — Inertia reloads fresh props
- The "Add Variant" on product edit creates a minimal variant (POST with defaults), user completes details on the detail page
