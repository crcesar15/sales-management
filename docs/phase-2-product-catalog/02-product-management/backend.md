# Backend — Product Management

> **Inertia-only module.** No API layer. All controllers render Inertia pages or redirect after mutations. The only JSON endpoint is the pending media upload (AJAX file upload before product exists).

## Files to Create or Modify

```
app/
├── Http/Controllers/Products/
│   ├── ProductController.php                              # MODIFY: add store/update/destroy/restore
│   └── ProductMediaController.php                         # NEW: pending media upload (JSON response)
├── Http/Requests/Products/
│   ├── StoreProductRequest.php                            # NEW
│   └── UpdateProductRequest.php                           # NEW
├── Http/Resources/Products/
│   └── ProductCollection.php                              # MODIFY: transform items for DataTable
├── Models/
│   ├── Product.php                                        # MODIFY: add HasMedia, LogsActivity, hasActiveVariants()
│   ├── ProductVariant.php                                 # MODIFY: remove HasMedia, add images() BelongsToMany
│   └── PendingMediaUpload.php                             # NEW: replaces PendingMedia — lightweight model implementing HasMedia
├── Policies/ProductPolicy.php                             # NEW
└── Services/Products/
    ├── ProductService.php                                 # NEW
    └── PendingMediaService.php                            # NEW

database/migrations/
├── *_create_media_product_variant_table.php               # NEW
├── *_create_pending_media_uploads_table.php               # NEW
├── *_add_barcode_to_product_variants_table.php            # NEW
├── *_add_indexes_to_products_table.php                    # NEW
├── *_add_indexes_to_product_variants_table.php            # NEW
└── *_drop_pending_media_table.php                         # NEW

routes/web.php                                             # MODIFY: expand product routes
```

## Implementation Order

Migration → Models → Policy → AuthServiceProvider → Form Requests → Collection → Services → Controllers → Routes

## Key Patterns

### Product Model

- Implements `HasMedia`, uses `InteractsWithMedia`, `SoftDeletes`, `LogsActivity`
- Register `'images'` collection + `thumb` conversion (368x232, sharpen 10) in `registerMediaConversions()`
- Add `hasActiveVariants(): bool` — checks `variants()->whereNotIn('status', ['archived'])->exists()`
- No enum cast on `status` — plain string values enforced at validation layer
- `getActivitylogOptions()` — `logFillable()`, `logOnlyDirty()`, `useLogName('product')`

### ProductVariant Model

- **Remove** `HasMedia` and `InteractsWithMedia` (images now live on Product)
- **Remove** `registerMediaConversions()`
- **Add** `images()` — `BelongsToMany` to `Media` via `media_product_variant` pivot with `->withPivot('position')->orderByPivot('position')`
- Keep `values()` — `BelongsToMany` to `ProductOptionValue` via `product_variant_option_values`
- Keep computed `name` attribute from option values

### PendingMediaUpload Model (replaces PendingMedia)

- Lightweight model implementing `HasMedia`
- `$fillable = ['user_id']`
- `registerMediaConversions()` — `thumb` conversion (368x232, sharpen 10)
- `registerMediaCollections()` — `'temp'` collection
- `user()` — `BelongsTo` to `User` (nullable)
- Media goes through Spatie immediately — thumbnails and conversions available in the response

### Default Variant Creation

`ProductService::create()` wraps in `DB::transaction()`:
1. Create Product record with validated data
2. Sync categories via `$product->categories()->sync($categoryIds)`
3. Commit pending media (if any pending media IDs in payload)
4. Create default `ProductVariant` with user-provided `price`, `stock`, and `barcode`; `status = 'active'`, `identifier = null`, zero option values

### ProductService Methods

| Method | Notes |
|--------|-------|
| `list(array $filters)` | Eager-load brand, measurementUnit, categories, media; `withCount('variants')`; filter by name (LIKE), brand_id, category_id (whereHas), status, trashed (onlyTrashed); order by name; paginate 20 with `withQueryString()` |
| `create(array $data)` | Transaction: product + default variant + category sync + commit pending media |
| `update(Product $product, array $data)` | Transaction: update fields, sync categories, commit pending media, remove requested media |
| `delete(Product $product)` | Guard: throw `\Exception` if `hasActiveVariants()`; then soft-delete |
| `restore(int $id)` | Raw ID lookup with `withTrashed()`; restore |

### PendingMediaService

| Method | Notes |
|--------|-------|
| `upload(UploadedFile $file): PendingMediaUpload` | Create record with `user_id = auth()->id()`, attach file via Spatie `addMedia()`. Returns model with real `thumb_url` and `full_url`. |
| `commit(Product $product, array $pendingMediaIds)` | For each ID: find `PendingMediaUpload` scoped to auth user, move its media to `$product` via Spatie's `move()`, delete the `PendingMediaUpload` record |
| `delete(PendingMediaUpload $pendingMedia)` | Delete record (cascades media via Spatie) |
| `purge()` | Delete all `PendingMediaUpload` records older than 24h (called by scheduled command) |

### Controller Actions

| Action | Method | Returns |
|--------|--------|---------|
| `index` | GET `/products` | `Inertia::render('Products/Index')` with paginated products + filters |
| `create` | GET `/products/create` | `Inertia::render('Products/Create')` with brands, categories, units |
| `store` | POST `/products` | `redirect()->route('products')` on success, `back()` on error |
| `edit` | GET `/products/{product}/edit` | `Inertia::render('Products/Edit')` with product (brand, categories, measurementUnit, variants.values.option, media, options.values) + brands, categories, units |
| `update` | PUT `/products/{product}` | `redirect()->route('products')` on success, `back()` on error |
| `destroy` | DELETE `/products/{product}` | `redirect()->route('products')` on success, `back()->with('error', ...)` on guard failure |
| `restore` | PUT `/products/{id}/restore` | `redirect()->route('products')` — uses raw ID, not route model binding |

`ProductMediaController` handles the pending media upload and returns JSON with `{ id, thumb_url, full_url }` (AJAX endpoint, not an Inertia page). Media lives on a `PendingMediaUpload` model with real Spatie conversions available immediately.

### Form Request Rules

| Field | Store | Update |
|-------|-------|--------|
| `name` | required, string, max:255 | sometimes, string, max:255 |
| `description` | nullable, string, max:350 | nullable, string, max:350 |
| `brand_id` | nullable, exists:brands,id | nullable, exists:brands,id |
| `measurement_unit_id` | nullable, exists:measurement_units,id | nullable, exists:measurement_units,id |
| `status` | required, in:active,inactive,archived | sometimes, in:active,inactive,archived |
| `categories_ids` | nullable, array | nullable, array |
| `categories_ids.*` | exists:categories,id | exists:categories,id |
| `price` | required, numeric, min:0 | sometimes, numeric, min:0 |
| `stock` | required, integer, min:0 | sometimes, integer, min:0 |
| `barcode` | nullable, string, max:100 | sometimes, nullable, string, max:100 |
| `pending_media_ids` | nullable, array | nullable, array |
| `pending_media_ids.*` | exists:pending_media_uploads,id,user_id,{auth.id} | exists:pending_media_uploads,id,user_id,{auth.id} |
| `remove_media_ids` | — | nullable, array |
| `remove_media_ids.*` | — | exists:media,id |

Authorization: `$this->user()?->can(PermissionsEnum::PRODUCTS_CREATE->value)` (or corresponding action).

### ProductPolicy

Follows `BrandPolicy` pattern. Methods: `viewAny`, `view`, `create`, `update`, `delete`, `restore`.

Register in `AuthServiceProvider`:
```php
Product::class => ProductPolicy::class,
```

### Routes

Flat routes inside existing `['auth']` group.

```
POST   /products/media/pending                          → media.store (JSON — returns { id, thumb_url, full_url })
DELETE /products/media/pending/{pendingMediaUpload}     → media.destroy (JSON)
GET    /products                                        → index
GET    /products/create                                 → create
POST   /products                                        → store
GET    /products/{product}/edit                         → edit
PUT    /products/{product}                              → update
DELETE /products/{product}                              → destroy
PUT    /products/{id}/restore                           → restore (withTrashed)
```

> `/products/media/pending` must be defined **before** any `{product}` route to prevent the segment being absorbed.

### ProductCollection

Follow `BrandCollection` pattern. Transform each product with: id, name, description, status, brand name, category names, first image thumb URL, `variants_count`. Include pagination `meta`.

## Permissions

Add `PRODUCTS_RESTORE` to `PermissionsEnum` and `PermissionSeeder`.

## Gotchas

1. **`HasMedia` is on Product, not ProductVariant.** Move `InteractsWithMedia` from `ProductVariant` to `Product`. The `registerMediaConversions` method moves too.
2. **`nullOnDelete()` on both FK columns** — brand/unit deletion nullifies the FK, does not delete the product. Current FKs use `NO ACTION` — migration needed.
3. **Restore uses raw `{id}`** — soft-deleted records are excluded from route model binding. Use `Product::withTrashed()->findOrFail($id)`.
4. **Pending media uses `PendingMediaUpload` model** — replaces the old `PendingMedia` model. Drop `pending_media` table and `PendingMedia` model.
5. **Delete guard = plain `\Exception`** — service throws, controller catches and redirects with flash error.
6. **Trait-based logging only** — `LogsActivity` with `logFillable()` + `logOnlyDirty()`. No manual `activity()` calls.
7. **`withCount('variants')`** avoids loading the full variant collection on list pages.
8. **`pending_media_ids` scoped to user** — validation ensures `user_id` matches authenticated user.
9. **`remove_media_ids` only on Update** — new products have no media to remove.
10. **`barcode` on default variant** — user-provided on product creation, stored on the default variant.
