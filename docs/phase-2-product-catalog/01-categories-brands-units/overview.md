# Task 01 — Categories, Brands & Measurement Units

## What
Three supporting catalog entities managed in a unified "Catalog Setup" admin page with tabs.

- **Categories** — logical product groupings (many-to-many with products)
- **Brands** — manufacturer/label attribution (one-to-many with products)
- **Measurement Units** — base unit for variant stock tracking (e.g., kg, L, pc)

## Why
Products cannot be created without these entities existing first — they satisfy FK constraints and power POS filtering and reporting.

## Requirements
- **Categories**: `name` unique max 50, soft deletes, M2M with products via `category_product` pivot
- **Brands**: `name` max 50, soft deletes, one-to-many with products (`brand_id` nullable)
- **Measurement Units**: `name` max 100, `abbreviation` max 10, soft deletes, referenced by `products.measurement_unit_id` (nullable)
- All three: list (searchable, paginated 15/page), create, edit, soft-delete, restore
- Admin-only — `products.manage` permission (Spatie Laravel Permission)
- Activity logging on all CUD operations (Spatie Activity Log)
- Block deletion if the entity has active (non-deleted) products

## Acceptance Criteria
- [ ] Admin can list, create, edit, soft-delete, and restore each entity
- [ ] Category names are unique; update ignores self-uniqueness
- [ ] Cannot delete an entity assigned to active products — returns 422
- [ ] All actions gate on `products.manage` permission; non-admin gets 403
- [ ] All CUD events appear in the activity log
- [ ] Tabbed UI at `/catalog/setup` shows Categories, Brands, Units tabs

## Dependencies
| Dependency | Reason |
|---|---|
| `spatie/laravel-permission` | `products.manage` gate |
| `spatie/laravel-activitylog` | Audit trail |
| Phase 1 — Auth & Roles | Permission must exist in DB |
| Task 02 (Products) | Consumes these entities as FKs |

## Notes
- **Migration bug**: `measurement_units` `down()` references `'measure_units'` — fix to `'measurement_units'` before running rollbacks in CI
- Pivot table `category_product` follows alphabetical Laravel convention (no timestamps, composite PK)
- `brand_id` and `measurement_unit_id` on products are nullable — products don't require these fields
- Abbreviation (e.g. "kg", "L") is displayed in the POS variant selector
