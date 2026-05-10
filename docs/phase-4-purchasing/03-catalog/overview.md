# Task 03: Catalog — Overview

## What
A product-centric catalog view that lists all product variants with vendor offerings, enabling price comparison and vendor selection for purchasing.

## Why
The vendor-scoped catalog (Task 02) answers "what does this vendor supply?" but purchasing workflows start from the product side: "who supplies this product and at what price?" This module provides that entry point — a single view to compare vendors, prices, MOQs, and lead times for any given variant.

## Requirements
- List all product variants that have at least one catalog entry
- Expand each variant row to show all vendor offerings (price, purchase unit, conversion factor, MOQ, lead time, status)
- Sort and filter by product name, brand, category, vendor, status
- Create/edit/delete actions redirect to the vendor-scoped catalog routes (`vendors.catalog.create`, `vendors.catalog.edit`, `vendors.catalog.destroy`)
- Only `active` catalog entries appear by default; toggle to show `inactive`
- Reuses the `catalog` table, model, service, and form requests from Task 02
- No new database tables or migrations
- Uses existing `CATALOG_VIEW`, `CATALOG_CREATE`, `CATALOG_EDIT`, `CATALOG_DELETE` permissions
- Admin-only; requires `catalog.view` permission for listing

## Acceptance Criteria
- [ ] Catalog index page loads with all variants that have catalog entries
- [ ] Each variant row expands to show vendor comparison (price, unit, MOQ, lead time)
- [ ] "Add Vendor" button on a variant redirects to vendor-scoped create form with variant pre-selected
- [ ] "Edit" and "Delete" actions on vendor entries redirect to vendor-scoped edit/destroy routes
- [ ] Filtering by product name, vendor, and status works correctly
- [ ] Only active entries shown by default; inactive entries visible when filter is set
- [ ] Legacy standalone `Catalog/Index.vue` and `Catalog/Edit/Index.vue` (Options API) are replaced

## Dependencies
- `catalog` table (Task 02) — all data comes from this table
- `product_variants` table (Phase 1) — variant names, identifiers, and product relationships
- `product_variant_units` table (Phase 2) — purchase unit and conversion factor display
- `vendors` table (Task 01) — vendor names for display
- Task 02 vendor-scoped routes — for create/edit/delete redirects

## Notes
- This is a read-only listing with action redirects; no standalone create/edit forms
- The product-centric view and the vendor-scoped view (Task 02) show the same data from different angles
- The `CatalogService` needs a new `listGroupedByProduct()` method that aggregates entries per variant
- Legacy `Catalog/Index.vue` and `Catalog/Edit/Index.vue` use Options API and the old pivot-based approach — they should be deleted and replaced