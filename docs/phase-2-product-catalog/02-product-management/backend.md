# Backend — Product Management

> **Inertia-only module.** No API layer. All controllers render Inertia pages or redirect after mutations. The only JSON endpoint is the pending media upload (AJAX file upload before product exists).

## Current State

| Component | Status |
|-----------|--------|
| `Product` model | Exists — needs `HasMedia` + `LogsActivity` added |
| `ProductVariant` model | Exists — needs `HasMedia` removed, `images()` pivot added |
| `ProductsController` | Exists — has `index`, `create`, `edit`; needs `store`, `update`, `destroy`, `restore`, `show` |
| Migrations | All tables exist; `media_product_variant` pivot is **new** |
| Permissions | `PRODUCTS_VIEW/CREATE/EDIT/DELETE` exist; `PRODUCTS_RESTORE` is **new** |
| `ProductService` | Does not exist yet |
| `PendingMediaService` | Does not exist yet |
| `PendingMediaUpload` model | Does not exist yet — lightweight model implementing `HasMedia` |

## Files to Create or Modify

```
app/
├── Enums/ProductStatus.php                                # NEW
├── Http/Controllers/Products/
│   ├── ProductController.php                              # MODIFY: add store/update/destroy/restore/show
│   └── ProductMediaController.php                         # NEW: pending media upload (JSON response)
├── Http/Requests/Products/
│   ├── StoreProductRequest.php                            # NEW
│   └── UpdateProductRequest.php                           # NEW
├── Http/Resources/Products/
│   └── ProductCollection.php                              # NEW: Inertia prop for DataTable pagination
├── Models/
│   ├── Product.php                                        # MODIFY: add HasMedia, LogsActivity, hasActiveVariants()
│   ├── ProductVariant.php                                 # MODIFY: remove HasMedia, add images() BelongsToMany
│   └── PendingMediaUpload.php                             # NEW: lightweight model implementing HasMedia
├── Policies/ProductPolicy.php                             # NEW
└── Services/Products/
    ├── ProductService.php                                 # NEW
    └── PendingMediaService.php                            # NEW

database/migrations/
├── *_create_media_product_variant_table.php               # NEW
└── *_create_pending_media_uploads_table.php               # NEW

routes/web.php                                             # MODIFY: expand product routes
```

## Implementation Order

Migration (`media_product_variant`, `pending_media_uploads`) → Enum → Models → Policy → Form Requests → Collection → Services → Controllers → Routes → AuthServiceProvider

## Key Patterns

### Product Model

- Implements `HasMedia`, uses `InteractsWithMedia`, `SoftDeletes`, `LogsActivity`
- Register `'images'` collection + `thumb` conversion (368x232, sharpen 10) in `registerMediaConversions()`
- Add `hasActiveVariants(): bool` — checks `variants()->whereNotIn('status', ['archived'])->exists()`
- Cast `status` to `ProductStatus` enum

### ProductVariant Model

- **Remove** `HasMedia` and `InteractsWithMedia` (images now live on Product)
- **Add** `images()` — `BelongsToMany` to `Media` via `media_product_variant` pivot
- Keep `values()` — `BelongsToMany` to `ProductOptionValue` via `product_variant_option_values`
- Keep computed `name` attribute from option values

### Default Variant Creation

`ProductService::create()` wraps in `DB::transaction()`:
1. Create Product record with validated data
2. Sync categories via `$product->categories()->sync($categoryIds)`
3. Commit pending media (if any pending media IDs in payload)
4. Create default `ProductVariant` with user-provided `price` and `stock`, `status = active`, `identifier = null`, zero option values

### ProductService Methods

| Method | Notes |
|--------|-------|
| `list(array $filters)` | Eager-load brand, measurementUnit, categories, media; `withCount('variants')`; filter by name, brand_id, category_id, status, trashed |
| `create(array $data)` | Transaction: product + default variant + category sync + commit pending media |
| `update(Product $product, array $data)` | Update fields, sync categories, commit/remove media |
| `delete(Product $product)` | Guard: throw `\Exception` if `hasActiveVariants()`; then soft-delete |
| `restore(int $id)` | Raw ID lookup with `withTrashed()`; restore |
| `commitPendingMedia(Product $product, array $pendingMediaIds)` | Iterate pending media IDs, move media from `PendingMediaUpload` to `Product` via Spatie's `move()`, delete `PendingMediaUpload` records |

### PendingMediaService

| Method | Notes |
|--------|-------|
| `upload(UploadedFile $file): PendingMediaUpload` | Create `PendingMediaUpload` record, attach file via Spatie `addMedia()`. Returns model with real `thumb_url` and `full_url`. |
| `commit(Product $product, array $pendingMediaIds)` | For each ID: find `PendingMediaUpload`, move its media to `$product` via Spatie's `move()`, delete the `PendingMediaUpload` record |
| `delete(int $id)` | Delete `PendingMediaUpload` record (and its media) |
| `purge()` | Delete all `PendingMediaUpload` records older than 24h (called by scheduled command) |

### Controller Actions

| Action | Method | Returns |
|--------|--------|---------|
| `index` | GET `/products` | `Inertia::render('Products/Index')` with paginated products + filters |
| `create` | GET `/products/create` | `Inertia::render('Products/Create')` with brands, categories, units |
| `store` | POST `/products` | `redirect()->route('products')` on success, `back()` on error |
| `show` | GET `/products/{product}` | `Inertia::render('Products/Show')` with product + variants + options (container for Task 03) |
| `edit` | GET `/products/{product}/edit` | `Inertia::render('Products/Edit')` with product + brands, categories, units |
| `update` | PUT `/products/{product}` | `redirect()->route('products')` on success, `back()` on error |
| `destroy` | DELETE `/products/{product}` | `redirect()->route('products')` on success, `back()->with('error', ...)` on guard failure |
| `restore` | PUT `/products/{id}/restore` | `redirect()->route('products')` — uses raw ID, not route model binding |

`ProductMediaController` handles the pending media upload and returns JSON with `{ id, thumb_url, full_url }` (AJAX endpoint, not an Inertia page). Media lives on a `PendingMediaUpload` model with real Spatie conversions available immediately.

### Form Request Rules

| Field | Store | Update |
|-------|-------|--------|
| `name` | required, string, max 255 | sometimes, string, max 255 |
| `description` | nullable, string, max 350 | nullable, string, max 350 |
| `brand_id` | nullable, exists:brands,id | nullable, exists:brands,id |
| `measurement_unit_id` | nullable, exists:measurement_units,id | nullable, exists:measurement_units,id |
| `status` | required, in:active,inactive,archived | sometimes, in:active,inactive,archived |
| `categories_ids` | nullable, array | nullable, array |
| `categories_ids.*` | exists:categories,id | exists:categories,id |
| `price` | required, numeric, min:0 | sometimes, numeric, min:0 |
| `stock` | required, integer, min:0 | sometimes, integer, min:0 |
| `pending_media_ids` | nullable, array | nullable, array |
| `pending_media_ids.*` | exists:pending_media_uploads,id | exists:pending_media_uploads,id |
| `remove_media_ids` | nullable, array | nullable, array |
| `remove_media_ids.*` | exists:media,id | exists:media,id |

Authorization: `$this->user()?->can(PermissionsEnum::PRODUCTS_CREATE->value)` (or corresponding action).

### Routes

Flat routes inside existing `['auth']` group. Restore route uses `->withTrashed()`.

```
GET    /products                          → index
GET    /products/create                   → create
POST   /products                          → store
GET    /products/{product}                → show
GET    /products/{product}/edit           → edit
PUT    /products/{product}                → update
DELETE /products/{product}                → destroy
PUT    /products/{id}/restore             → restore (withTrashed)
POST   /products/media/pending            → media.store (JSON — returns { id, thumb_url, full_url })
DELETE /products/media/pending/{pendingMediaUpload}  → media.destroy (JSON)
```

> `/products/media/pending` must be defined **before** any `{product}` route to prevent the segment being absorbed.

### ProductCollection

Follow `UserCollection` pattern. Used as Inertia prop for DataTable pagination. Include pagination `meta` and transform each product with: id, name, description, status, brand name, category names, first image thumb URL, `variants_count`.

## Gotchas

1. **`HasMedia` is on Product, not ProductVariant.** Move `InteractsWithMedia` from `ProductVariant` to `Product`. The `registerMediaConversions` method moves too.
2. **`nullOnDelete()` on both FK columns** — brand/unit deletion nullifies the FK, does not delete the product.
3. **Restore uses raw `{id}`** — soft-deleted records are excluded from route model binding. Use `Product::withTrashed()->findOrFail($id)`.
4. **Pending media uses `PendingMediaUpload` model** — not temp files on disk. Media goes through Spatie immediately, so thumbnails and conversions are available in the response. Cleanup is just deleting old `PendingMediaUpload` records.
5. **Delete guard = plain `\Exception`** — service throws, controller catches and redirects with flash error. Follows Task 01 pattern.
6. **Trait-based logging only** — `LogsActivity` with `logFillable()` + `logOnlyDirty()`. No manual `activity()` calls.
7. **`withCount('variants')`** avoids loading the full variant collection on list pages.
8. **`PRODUCTS_RESTORE`** must be added to `PermissionsEnum`, `PermissionSeeder`, and assigned to admin role.
