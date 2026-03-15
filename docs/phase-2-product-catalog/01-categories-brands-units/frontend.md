# Frontend — Categories, Brands & Measurement Units

## Pages & Components

```
resources/js/
├── Pages/CatalogSetup/
│   ├── Index.vue                  # Main tabbed page
│   └── Partials/
│       ├── CategoriesTab.vue      # DataTable + create/edit Dialog
│       ├── BrandsTab.vue          # DataTable + create/edit Dialog
│       └── UnitsTab.vue           # DataTable + create/edit Dialog (+ abbreviation field)
```

## PrimeVue Components Used

| Component | Usage |
|---|---|
| `TabView` / `TabPanel` | Three-tab layout on `Index.vue` |
| `DataTable` + `Column` | Paginated lazy list per entity |
| `Button` | Create, edit, delete, restore actions |
| `Dialog` | Modal for create/edit forms |
| `InputText` | Name (and abbreviation) fields |
| `Tag` | Active / Deleted status badge |
| `Toast` (via `useToast`) | Success/error feedback |

## Key Inertia Patterns

**Tab state — Pinia store (optional but recommended):**
```ts
// stores/catalogSetup.ts
export const useCatalogSetupStore = defineStore('catalogSetup', () => {
  const activeTab = ref(0)
  return { activeTab }
})
```

**Server-side search (preserves tab state):**
```ts
router.get(route('catalog.setup.index'), { search: search.value }, {
  preserveState: true, replace: true,
})
```

**Create with `useForm`:**
```ts
const form = useForm({ name: '' })
form.post(route('catalog.setup.categories.store'), {
  onSuccess: () => { dialogVisible.value = false }
})
```

**Update with `useForm`:**
```ts
form.put(route('catalog.setup.categories.update', editing.value.id), {
  onSuccess: () => { dialogVisible.value = false }
})
```

**Delete + restore:**
```ts
router.delete(route('...destroy', id), { onError: (e) => toast.add(...) })
router.patch(route('...restore', id))
```

## DataTable Setup (lazy server-side pagination)
```vue
<DataTable :value="data.data" lazy paginator
  :rows="data.meta.per_page" :total-records="data.meta.total"
  :first="(data.meta.current_page - 1) * data.meta.per_page"
  @page="onPage" class="p-datatable-sm">
```

## Form Dialog Pattern
- Single `dialogVisible` ref + `editingItem` ref per tab
- `form.reset()` before opening create; `form.name = item.name` before opening edit
- Bind `p-invalid` class and `<small>` error text to `form.errors.name`
- `MeasurementUnit` form adds a second `InputText` for `abbreviation`

## TypeScript Types
```ts
interface CategoryResource  { id: number; name: string; deleted_at: string | null }
interface BrandResource     { id: number; name: string; deleted_at: string | null }
interface UnitResource      { id: number; name: string; abbreviation: string; deleted_at: string | null }
```

## Gotchas
- `preserveState: true` on search keeps the active tab in view
- Always call `form.reset()` before opening the create dialog to clear stale edit data
- Use `show-clear` on any dropdowns that reference these entities in product forms
