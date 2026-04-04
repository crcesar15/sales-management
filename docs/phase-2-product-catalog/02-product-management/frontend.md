# Frontend — Product Management

> **Inertia-only.** All data comes from Inertia props. No API composable calls. Forms submit via Inertia `router` with VeeValidate + Yup validation. Server-side pagination and filtering via `router.visit()`.

## Current State

| File | Status |
|------|--------|
| `Pages/Products/Index.vue` | Exists — uses `useProductClient` API calls; needs refactor to Inertia props |
| `Pages/Products/Create/Index.vue` | Exists — uses Vuelidate; needs refactor to VeeValidate + Inertia submission |
| `Pages/Products/Edit/Index.vue` | Exists — uses Vuelidate; needs refactor to VeeValidate + Inertia submission |
| `Components/MediaManager.vue` | Exists — handles file uploads; needs update for `PendingMediaUpload` flow |
| `Types/product-types.ts` | Exists — needs update for new props shape |

## Refactoring Scope

- **List/fetch** → `router.visit()` with `preserveState: true` (server-side pagination/filtering via Inertia props)
- **Create/update** → VeeValidate + Yup schema + Inertia `router.post()` / `router.put()` for submission
- **Delete/restore** → Inertia `router.delete()` / `router.put()` with `useConfirm`
- **Remove** API composable imports (`useProductClient`, `useMediaClient`)
- **Replace** Vuelidate with VeeValidate + Yup
- **Update** MediaManager to use `PendingMediaUpload` endpoint

## Page Structure

```
resources/js/
├── Pages/Products/
│   ├── Index.vue                   # DataTable + filters + trashed toggle
│   ├── Create/
│   │   └── Index.vue               # Product form + default variant fields + image upload
│   ├── Edit/
│   │   └── Index.vue               # Pre-filled product form + image management
│   ├── Show/
│   │   └── Index.vue               # Product detail — container for Task 03 (options/variants panels)
│   └── Partials/
│       ├── ProductForm.vue         # Shared form fields (name, desc, status, brand, categories, images)
│       ├── ProductImages.vue       # Image gallery with upload/remove (uses PendingMediaUpload)
│       └── DefaultVariantFields.vue # Price + stock inputs for the default variant
└── Types/
    └── product-types.ts            # Updated for new props shape
```

### Suggestion: Extract `ProductForm.vue` and `DefaultVariantFields.vue`

Create and Edit pages share most form logic. Extract a `ProductForm.vue` partial that receives initial values and emits a submit event. The `DefaultVariantFields.vue` partial handles price and stock for the default variant — shown on Create (editable) and on Edit when the product is still simple (single variant, no options).

## Key Inertia Patterns

### Props (from controller)

| Page | Key Props |
|------|-----------|
| `Index.vue` | `products` (paginated collection with `data` + `meta`), `filters` (search, brand_id, category_id, status, trashed), `brands`, `categories` (for filter dropdowns) |
| `Create/Index.vue` | `brands`, `categories`, `measurementUnits` (for form dropdowns) |
| `Edit/Index.vue` | `product` (full resource with brand, categories, media, variants), `brands`, `categories`, `measurementUnits` |
| `Show/Index.vue` | `product` (full resource with brand, categories, media, variants, options) |

### Server-side Pagination/Filtering

DataTable events (`@page`, `@sort`) and filter changes call `router.visit()` instead of API fetch. This reloads page props without a full page refresh.

### Form Validation — VeeValidate + Yup

Schema with `toTypedSchema()`. Submit via Inertia `router.post()` or `router.put()`. On server validation error, call `setErrors()` to display field errors. On success, redirect and show toast.

### Default Variant Fields on Create

The product create form includes **price** and **stock** fields for the auto-created default variant. These are part of the same form submission — not a separate step. Label them as "Base Price" and "Base Stock" in the UI to clarify they belong to the variant, not the product.

On Edit: if the product is still simple (one variant, no options), show the default variant's price as editable. If the product is configurable, variant editing moves to the Task 03 panels.

## Pending Media Upload Flow

`ProductImages.vue` uploads each file immediately to the `PendingMediaUpload` endpoint (not tied to form submit):

1. User selects file → `FileUpload` `@select` event fires
2. Component posts to `POST /products/media/pending` → receives `{ id, thumb_url, full_url }`
3. **Real Spatie thumbnails available immediately** — show in gallery without waiting for product save
4. Component tracks `pending_media_ids` array in the form payload
5. User removes a pending image → `DELETE /products/media/pending/{id}` → removes from array
6. User removes an existing image → adds `id` to `remove_media_ids` array
7. On form submit, server receives `pending_media_ids` and `remove_media_ids` arrays

### Suggestion: Enhance `ProductImages.vue` with Drag-to-Reorder

Since images are displayed as a gallery, consider adding drag-and-drop reorder. The order can be stored in the media's `order_column` (Spatie manages this) or as a `custom_property` — sent as `media_order: [id1, id2, ...]` in the payload.

## PrimeVue Components

| Component | Usage |
|---|---|
| `DataTable` + `Column` | Lazy paginated product list |
| `InputText` | Name search, form fields |
| `Dropdown` | Brand / unit / status filters and form fields |
| `MultiSelect` | Category multi-select (`display="chip"`) |
| `Textarea` | Description with character counter (max 350) |
| `FileUpload` | Auto-mode image upload trigger |
| `Button` | All actions |
| `Tag` | Status badge (active=success, inactive=warn, archived=danger) |
| `Toast` | Feedback notifications |
| `ConfirmDialog` | Delete/restore confirmation |

## TypeScript Types

Key additions needed:

| Type | Change |
|------|--------|
| `ProductResponse` | Add `media: MediaResponse[]`, `variants_count: number`, ensure `brand`/`measurement_unit` are full objects |
| `ProductPayload` | Replace `media?: {id:number}[]` with `pending_media_ids: number[]`, `remove_media_ids: number[]`, `categories_ids: number[]`, add `price: number`, `stock: number` (for default variant) |
| `MediaResponse` | Add `thumb_url: string`, `full_url: string` (for both attached and pending media) |
| `PendingMediaUploadResponse` | New: `{ id: number, thumb_url: string, full_url: string }` |

## Gotchas

1. **No `useProductClient` calls.** All data comes from Inertia props. Use `router.visit()` for pagination/filtering.
2. **VeeValidate, not Vuelidate.** Existing pages use Vuelidate — refactor to VeeValidate + Yup with `toTypedSchema()` per project convention.
3. **`pending_media_ids` not UUIDs.** The upload endpoint returns a `PendingMediaUpload` model ID with real thumbnail URLs. No temp file paths.
4. **`preserveState: true`** on `router.visit()` keeps local component state intact during pagination/filtering.
5. **Character counter** on description — show `length / 350` in real-time. Enforce in Yup schema (`max(350)`).
6. **`show-clear` on brand/unit dropdowns** lets admin set FK to null.
7. **Flash errors** from backend: `$page.props.flash.error` on delete guard failure — show via toast.
8. **Default variant fields** only on Create and on Edit when product is simple. Don't show when product is configurable (Task 03 handles variant editing).
9. **`v-can` directive** for permission-based button visibility: `v-can="'product.create'"`, `v-can="'product.delete'"`.
