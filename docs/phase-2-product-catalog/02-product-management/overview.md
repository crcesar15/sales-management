# Task 02 — Product Management

## What
Core product entity — the central catalog item linking brands, categories, units, media, and variants.
A product (e.g., "Coca-Cola 500ml Can") is the top-level record; its sellable configurations are handled via variants (Task 03).

## Why
Products are the fundamental sellable items. Without them, no sales, POS, or inventory workflow is possible.

## Requirements
- **Fields**: `brand_id` (nullable FK), `measurement_unit_id` (nullable FK), `name`, `description` (max 350 chars), `status` (`active`/`inactive`/`archived`), `deleted_at`
- **Categories**: many-to-many via `category_product` pivot
- **Images**: Spatie Media Library, collection `'images'`, multiple images per product
- **Pending media workflow**: upload images before product exists → get UUID → commit UUIDs on save
- **Listing**: paginated 20/page, searchable by name, filterable by brand/category/status
- Soft-deleted products excluded by default; `trashed=true` filter shows deleted
- Admin-only — `products.manage` permission

## Acceptance Criteria
- [ ] Admin can list products with search and filters (brand, category, status)
- [ ] Admin can create a product with all fields + categories + images
- [ ] Admin can edit product details, add/remove images, change status
- [ ] Admin can soft-delete (blocked if active variants exist) and restore
- [ ] Trashed filter shows deleted products
- [ ] Product list response includes brand name, category names, first image thumb
- [ ] All CUD operations are permission-gated and activity-logged
- [ ] Images upload independently before product save (pending media workflow)

## Dependencies
| Dependency | Reason |
|---|---|
| Task 01 (Categories, Brands, Units) | FK constraints must exist |
| `spatie/laravel-medialibrary` | Image upload and storage |
| `spatie/laravel-activitylog` | Audit trail |
| Task 03 (Variants) | Product show page hosts variants UI |

## Notes
- `name` is not unique at DB level (same name under different brands is valid)
- `description` max 350 chars — enforce at validation layer AND show a character counter in UI
- **Pending media** uses a UUID-based temporary storage on disk; a cleanup command should purge files older than 24h
- Eager-load `brand`, `measurementUnit`, `categories`, `media` on list to prevent N+1
- `archived` products remain in order history but are hidden from POS active item list
- Block deletion if `variants()->whereNotIn('status', ['archived'])->exists()`
