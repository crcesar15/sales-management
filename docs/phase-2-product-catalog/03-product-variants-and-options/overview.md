# Task 03 — Product Variants & Options

## What
The variant system gives products multiple sellable configurations.

- **Options** (e.g., "Color") with **Option Values** (e.g., "Red", "Blue") define the dimension axes
- **Variants** are the sellable instances (e.g., "Red / Large"), each with its own SKU, price, stock, and status

**Two creation modes:**
1. **Auto-generate** — define options + values → system creates every combination (2 colors × 3 sizes = 6 variants)
2. **Manual** — add one variant at a time, selecting specific option values

## Why
Retail products exist in multiple configurations tracked independently for pricing and inventory.
A single "T-Shirt" product may have 12 variants — each with a different price and stock level.

## Requirements
- **`product_options`**: `id`, `product_id`, `name` (e.g. "Color")
- **`product_option_values`**: `id`, `product_option_id`, `value` (e.g. "Red")
- **`product_variants`**: `id`, `product_id`, `identifier` (SKU/barcode, unique nullable), `price` (DECIMAL 10,2), `stock` (INT ≥ 0), `status` (active/inactive/archived)
- **`product_variant_option_values`** (pivot): `product_variant_id`, `product_option_value_id` — no timestamps, composite PK
- Auto-generate creates all combinations; default price `0.00`, default stock `0`
- Auto-generate blocked if variants already exist (prevents overwrite)
- Manual mode: select option values per variant when adding
- `identifier` uniqueness enforced at DB level (partial unique index on non-null values)
- Admin-only — `products.manage` permission

## Acceptance Criteria
- [ ] Admin can view all options, values, and variants for a product
- [ ] Admin can add/edit/delete options and their values
- [ ] Deleting an option value blocked if any variant uses it
- [ ] Admin can auto-generate variants from option combinations — preview shows combination count first
- [ ] Auto-generate blocked if variants already exist
- [ ] Admin can manually add a variant with selected option values
- [ ] Admin can edit variant identifier, price, stock, status
- [ ] Duplicate option value combinations per product are prevented
- [ ] All CUD operations are permission-gated and activity-logged

## Dependencies
| Dependency | Reason |
|---|---|
| Task 02 (Products) | Variants are scoped to a product |
| Task 04 (Sale Units) | Sale units reference `product_variant_id` |
| Task 01 (Units) | Base unit label derived from `product.measurement_unit` |
| Phase 3 (Inventory) | Stock column written by inventory adjustments |

## Notes
- `stock` is the live inventory count — Phase 3 writes to this column via adjustment records; do NOT update it manually outside of inventory workflows
- Each variant should have exactly one value per option defined on the product (enforced at application level)
- The product `Show` page is the container for this UI
- `identifier` partial unique index: `CREATE UNIQUE INDEX ... WHERE identifier IS NOT NULL`
