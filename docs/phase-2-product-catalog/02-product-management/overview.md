# Task 02 — Product Management

## What

Core product entity — the central catalog record linking brands, categories, measurement units, media, and variants.

A product (e.g., "Coca-Cola 500ml Can") is the top-level record; its sellable configurations are handled via variants. **A product is NOT directly sellable** — every sellable item is a `product_variant`. On creation, a product automatically receives a single **default variant** (no option values) so that downstream systems (orders, POS, inventory) always reference `product_variant_id`.

Images live on the **Product** model via Spatie Media Library. A separate pivot table (`media_product_variant`) allows logical image-to-variant associations without duplicating files.

The `product_variants` base table is **owned by this task** for the default variant. Task 03 extends it with multi-variant workflows (options, auto-generate, manual add).

## Why

Products are the fundamental catalog items. Without them, no sales, POS, or inventory workflow is possible. The default variant guarantees that every product has at least one sellable SKU from the moment of creation, eliminating the need for downstream systems to handle a "product without variant" edge case.

## Requirements

### Product Entity

- **Fields**: `brand_id` (nullable FK), `measurement_unit_id` (nullable FK), `name`, `description` (max 350 chars), `status` (`active`/`inactive`/`archived`, default `active`), `deleted_at` (soft deletes)
- **Model**: `Product` implements `HasMedia` (Spatie), uses `InteractsWithMedia`, `SoftDeletes`, `LogsActivity`
- `name` is not unique at DB level (same name under different brands is valid)

### Categories

- Many-to-many via `category_product` pivot (composite PK, no timestamps)
- Synced via `$product->categories()->sync($categoryIds)`

### Image Management

- **HasMedia on Product** (moved from `ProductVariant`). Collection `'images'`, multiple images per product.
- Media conversions: `thumb` (368x232, sharpen 10) registered in `registerMediaConversions()`.
- **Pending media via `PendingMediaUpload` model** — upload before product exists:
  - `PendingMediaUpload` is a lightweight Eloquent model implementing `HasMedia` — acts as a temporary container for images before the product exists
  1. `POST /products/media/pending` — creates a `PendingMediaUpload`, attaches file via Spatie, returns `{ id, thumb_url, full_url }`
  2. Frontend stores the pending media IDs and displays real thumbnails immediately
  3. Product store/update payload includes `pending_media_ids: [1, 2, ...]`
  4. Service moves each media from `PendingMediaUpload` to the `Product` via Spatie's `move()` method, then deletes the `PendingMediaUpload` record
  5. `remove_media_ids` in payload calls `$product->deleteMedia($id)` per entry
  6. Scheduled command deletes `PendingMediaUpload` records (and their media) older than 24h

### media_product_variant Pivot (NEW)

- Purpose: logical association between product images and specific variants without duplicating files
- Schema: `id`, `media_id` (FK to media, cascade delete), `product_variant_id` (FK to product_variants, cascade delete), timestamps
- **Inheritance rule**: a variant with zero rows in this pivot inherits ALL product images. A variant with one or more rows shows only its explicitly associated images.
- Eloquent: `BelongsToMany` on `ProductVariant` → `images()` via this pivot

### Default Variant (Auto-Created)

- On product creation, `ProductService::create()` wraps the operation in `DB::transaction()`:
  1. Creates the Product record
  2. Creates a `ProductVariant` with: `product_id` = new product, `identifier` = null, `price` = user-provided value, `stock` = user-provided value, `status` = `active`, zero option values
- The product create form exposes **price** and **stock** fields for the default variant alongside product fields
- The default variant is never deleted independently — it lives as long as the product does (cascade on delete)
- When a product transitions to configurable (Task 03), this default variant is **kept and assigned option values** ("keep & edit"). It is not archived or replaced.

### Listing, Search, Filters, Pagination

- Paginated at 20 per page, ordered by name
- Searchable by `name` (partial match, LIKE)
- Filterable by: `brand_id`, `category_id` (via `whereHas`), `status`, `trashed` (boolean for soft-deleted)
- Eager-load: `brand`, `measurementUnit`, `categories`, `media`
- `withCount('variants')` to avoid loading full variant collection
- Soft-deleted products excluded by default; `trashed=true` filter shows deleted

### Soft Delete with Variant Protection

- Soft delete blocked if product has non-archived variants: `variants()->whereNotIn('status', ['archived'])->exists()`
- If only archived variants exist, soft delete is allowed
- Restore clears `deleted_at`; requires `PRODUCTS_RESTORE` permission
- `restore` endpoint uses raw `{id}` lookup (not route model binding, since soft-deleted records are excluded from binding)

### Permission Gating

- Granular permissions using `PermissionsEnum`:
  - `product.view` (`PRODUCTS_VIEW`) — list and show
  - `product.create` (`PRODUCTS_CREATE`) — create
  - `product.edit` (`PRODUCTS_EDIT`) — update
  - `product.delete` (`PRODUCTS_DELETE`) — soft delete
  - `product.restore` (`PRODUCTS_RESTORE`) — **NEW**, needs to be added to `PermissionsEnum` and `PermissionSeeder`, assigned to admin role
- Policy: `ProductPolicy` with methods `viewAny`, `view`, `create`, `update`, `delete`, `restore`
- All CUD operations logged via Spatie Activity Log (`LogsActivity` trait on Product model)

### ProductService

- Located at `app/Services/Products/ProductService.php`
- Methods: `list(array $filters)`, `create(array $data)`, `update(Product $product, array $data)`, `delete(Product $product)`, `restore(int $id)`, `commitPendingMedia(Product $product, array $pendingMediaIds)`
- `create()` wraps product creation + default variant creation in `DB::transaction()`
- `delete()` checks `hasActiveVariants()` guard before soft-deleting
- Controller is thin; delegates all business logic to the service

## Acceptance Criteria

- [ ] Admin can list products with search and filters (brand, category, status, trashed)
- [ ] Admin can create a product with all fields + categories + images; a default variant is auto-created with user-provided price/stock, status=active, no option values
- [ ] Admin can edit product details, add/remove images, change status
- [ ] Admin can soft-delete a product (blocked if active/inactive variants exist; allowed if only archived variants remain)
- [ ] Admin with `product.restore` permission can restore a soft-deleted product
- [ ] Trashed filter shows deleted products
- [ ] Product list response includes brand name, category names, first image thumb, `variants_count`
- [ ] Product detail response includes full image list with URLs and thumbs, all variants
- [ ] All CUD operations are permission-gated (granular per action) and activity-logged
- [ ] Images upload independently before product save (pending media via `PendingMediaUpload` model — real thumbnails and URLs available immediately)
- [ ] `media_product_variant` pivot table exists and is queryable
- [ ] `PRODUCTS_RESTORE` permission exists in `PermissionsEnum`, `PermissionSeeder`, and is assigned to admin role

## Dependencies

| Dependency | Reason |
|---|---|
| Task 01 (Categories, Brands, Units) | FK constraints must exist |
| `spatie/laravel-medialibrary` | Image upload, storage, and conversions |
| `spatie/laravel-activitylog` | Audit trail |
| `spatie/laravel-permission` | Granular permission checks |
| Task 03 (Variants & Options) | Product show page hosts variants UI; default variant created here, multi-variant managed there |

## Notes

- `description` max 350 chars — enforce at validation layer AND show a character counter in UI
- **Pending media** uses a `PendingMediaUpload` model (implements `HasMedia`) as a temporary container. A cleanup command deletes records older than 24h. Images get real Spatie conversions and URLs from the moment of upload.
- Eager-load `brand`, `measurementUnit`, `categories`, `media` on list to prevent N+1
- `archived` products remain in order history but are hidden from POS active item list
- Block deletion if `variants()->whereNotIn('status', ['archived'])->exists()`
- **HasMedia** moves from `ProductVariant` to `Product`. Remove `InteractsWithMedia` and `HasMedia` from `ProductVariant` model; add to `Product`. The `registerMediaConversions` method moves to `Product`.
- `restore` uses raw `{id}` not route model binding (soft-deleted records excluded from binding)
- `withCount('variants')` avoids loading the full variant collection on list pages
- The `product_variants` table (already migrated) is owned by this task for the default variant. Task 03 extends it with multi-variant management.
- Default variant's `price` and `stock` are exposed in the product creation form as the product's "base price" and "base stock"
