# Frontend — Product Management

## Pages & Components

```
resources/js/
├── Pages/Products/
│   ├── Index.vue                   # Paginated list with filters
│   ├── Create.vue                  # useForm + ProductForm
│   ├── Edit.vue                    # useForm pre-filled + ProductForm
│   ├── Show.vue                    # Product detail + variants container (Task 03)
│   └── Partials/
│       ├── ProductForm.vue         # Shared form fields (name, desc, status, brand, categories, images)
│       └── ProductImageUpload.vue  # Pending media upload widget
└── Components/Products/
    └── ProductStatusBadge.vue      # Colored Tag for active/inactive/archived
```

## PrimeVue Components Used

| Component | Usage |
|---|---|
| `DataTable` + `Column` | Lazy paginated product list |
| `InputText` | Name search |
| `Dropdown` | Brand / category / status filters; brand & unit in form |
| `MultiSelect` | Category multi-select (`display="chip"`) in form |
| `Textarea` | Description with character counter |
| `FileUpload` | Auto-mode image upload trigger |
| `Button` | All actions |
| `Tag` | Status badge in table |
| `Toast` | Feedback notifications |

## Key Inertia Patterns

**Filter bar — `router.get` with `replace: true`:**
```ts
router.get(route('products.index'), {
  search: search.value || undefined,
  brand_id: brandId.value || undefined,
  status: status.value || undefined,
}, { preserveState: true, replace: true })
```

**Create form:**
```ts
const form = useForm({
  name: '', description: '', status: 'active',
  brand_id: null, measurement_unit_id: null,
  category_ids: [], pending_media_uuids: [], remove_media_ids: [],
})
form.post(route('products.store'))
```

**Edit form — pre-fill from prop:**
```ts
const form = useForm({
  name: product.name, description: product.description ?? '',
  status: product.status, brand_id: product.brand?.id ?? null,
  // ...
  pending_media_uuids: [], remove_media_ids: [],
})
form.put(route('products.update', product.id))
```

## Pending Media Upload Flow

`ProductImageUpload.vue` uploads each file immediately via `axios.post` (not tied to form submit):
1. User selects file → `FileUpload` `@select` event fires
2. Component posts to `/products/media/pending` → receives `{ uuid, url }`
3. Emits `uploaded(uuid, url)` → parent pushes `uuid` into `form.pending_media_uuids`
4. User removes pending image → component deletes via `axios.delete` → emits `pending-removed(uuid)` → parent filters array
5. User removes existing image → emits `existing-removed(id)` → parent pushes `id` into `form.remove_media_ids`
6. On form submit, server receives `pending_media_uuids` and `remove_media_ids` arrays

## TypeScript Types (reference)
```ts
interface ProductResource {
  id: number; name: string; description: string | null
  status: 'active' | 'inactive' | 'archived'
  brand: { id: number; name: string } | null
  measurement_unit: { id: number; name: string; abbreviation: string } | null
  categories: { id: number; name: string }[]
  images: { id: number; url: string; thumb: string }[]
  variants_count?: number; deleted_at: string | null
}
```

## Gotchas
- `router.get` with `replace: true` on filter change keeps browser history clean
- `form.reset()` is not needed for Edit — initialize `useForm` directly from the prop
- `FileUpload` in `auto` mode fires `@select` immediately; show a spinner overlay on the thumbnail while uploading
- Show `character_count / 350` below the description textarea in real-time using `computed`
- `show-clear` on brand/unit dropdowns lets admin set FK to null
