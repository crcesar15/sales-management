# Frontend — Product Management

> **Inertia-only.** All data comes from Inertia props. No API composable calls. Forms submit via Inertia `router` with VeeValidate + Yup validation. Server-side pagination and filtering via `router.visit()`.

## Page Structure

```
resources/js/
├── Pages/Products/
│   ├── Index.vue                   # DataTable + filters + trashed toggle
│   ├── Create/
│   │   └── Index.vue               # Product form + default variant fields + image upload
│   ├── Edit/
│   │   └── Index.vue               # Pre-filled product form + image management (also serves as detail view)
│   └── Components/
│       ├── ProductForm.vue         # Shared form fields (name, desc, status, brand, categories, images)
│       ├── ProductImages.vue       # Image gallery with upload/remove (uses PendingMediaUpload)
│       └── DefaultVariantFields.vue # Price + stock + barcode inputs for the default variant
└── Types/
    └── product-types.ts            # Updated for new props shape
```

### Shared Components

Create and Edit pages share most form logic. Extract a `ProductForm.vue` component that receives initial values and emits a submit event. The `DefaultVariantFields.vue` component handles price, stock, and barcode for the default variant — shown on Create (editable) and on Edit when the product is still simple (single variant, no options).

## Key Inertia Patterns

### Props (from controller)

| Page | Key Props |
|------|-----------|
| `Index.vue` | `products` (paginated collection with `data` + `meta`), `filters` (filter, brand_id, category_id, status, trashed), `brands`, `categories` (for filter dropdowns) |
| `Create/Index.vue` | `brands`, `categories`, `measurementUnits` (for form dropdowns) |
| `Edit/Index.vue` | `product` (with brand, categories, measurementUnit, variants.values.option, media, options.values), `brands`, `categories`, `measurementUnits` |

### Server-side Pagination/Filtering

DataTable events (`@page`, `@sort`) and filter changes call `router.visit()` instead of API fetch. This reloads page props without a full page refresh.

### Form Validation — VeeValidate + Yup

Schema with `toTypedSchema()`. Submit via Inertia `router.post()` or `router.put()`. On server validation error, call `setErrors()` to display field errors. On success, redirect and show toast.

### Default Variant Fields on Create

The product create form includes **price**, **stock**, and **barcode** fields for the auto-created default variant. These are part of the same form submission — not a separate step. Label them as "Base Price", "Base Stock", and "Barcode" in the UI to clarify they belong to the variant, not the product.

On Edit: if the product is still simple (one variant, no options), show the default variant's price, stock, and barcode as editable. If the product is configurable, variant editing moves to the Task 03 panels.

## Pending Media Upload Flow

`ProductImages.vue` uploads each file immediately to the `PendingMediaUpload` endpoint (not tied to form submit):

1. User selects file → `FileUpload` `@select` event fires
2. Component posts to `POST /products/media/pending` → receives `{ id, thumb_url, full_url }`
3. **Real Spatie thumbnails available immediately** — show in gallery without waiting for product save
4. Component tracks `pending_media_ids` array in the form payload
5. User removes a pending image → `DELETE /products/media/pending/{id}` → removes from array
6. User removes an existing image → adds `id` to `remove_media_ids` array
7. On form submit, server receives `pending_media_ids` and `remove_media_ids` arrays

### Image Ordering

- **Product images**: order managed by Spatie's `order_column` — drag-and-drop reorder sends `media_order: [id1, id2, ...]` in the payload
- **Variant images**: order managed by `position` column on the `media_product_variant` pivot (handled in Task 03)

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

Key types needed:

| Type | Shape |
|------|-------|
| `ProductResponse` | `id`, `name`, `description`, `status`, `brand` (object or null), `measurement_unit` (object or null), `categories` (array), `media` (array with `thumb_url`/`full_url`), `variants_count`, `created_at` |
| `ProductPayload` | `name`, `description`, `brand_id`, `measurement_unit_id`, `status`, `categories_ids: number[]`, `price: number`, `stock: number`, `barcode?: string`, `pending_media_ids: number[]`, `remove_media_ids: number[]` |
| `PendingMediaUploadResponse` | `{ id: number, thumb_url: string, full_url: string }` |
| `ProductFilters` | `filter?: string`, `brand_id?: number`, `category_id?: number`, `status?: string`, `trashed?: boolean`, `order_by`, `order_direction`, `per_page` |

## Gotchas

1. **No API composable calls.** All data comes from Inertia props. Use `router.visit()` for pagination/filtering.
2. **VeeValidate + Yup** with `toTypedSchema()` for form validation per project convention.
3. **`pending_media_ids` are integer IDs** — the upload endpoint returns a `PendingMediaUpload` model ID with real thumbnail URLs. No temp file paths or UUIDs.
4. **`preserveState: true`** on `router.visit()` keeps local component state intact during pagination/filtering.
5. **Character counter** on description — show `length / 350` in real-time. Enforce in Yup schema (`max(350)`).
6. **`show-clear` on brand/unit dropdowns** lets admin set FK to null.
7. **Flash errors** from backend: `$page.props.flash.error` on delete guard failure — show via toast.
8. **Default variant fields** only on Create and on Edit when product is simple. Don't show when product is configurable (Task 03 handles variant editing).
9. **`v-can` directive** for permission-based button visibility: `v-can="'product.create'"`, `v-can="'product.delete'"`.
10. **`barcode` on default variant form** — user-provided during product creation, stored on the default variant.
