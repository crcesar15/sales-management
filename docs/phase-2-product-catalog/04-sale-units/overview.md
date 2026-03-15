# Task 04 — Sale Units

## What
Sale units define the different packaging/quantities in which a variant can be sold at the POS.

**Example:** A variant "Mineral Water 500ml" has base unit "Piece" (from the product's measurement unit).
Sale units add:
- **Bottle** → `conversion_factor = 1` (same as base)
- **6-Pack** → `conversion_factor = 6`
- **Crate of 24** → `conversion_factor = 24`

The POS displays all active sale units for the variant. When a sale unit is selected, stock is deducted by `qty_sold × conversion_factor`.

## Why
Retail stores sell the same product in multiple packaging sizes at different price points. Tracking stock in a single base unit while selling in various pack sizes requires a conversion layer.

## Requirements
- **New table** `product_variant_sale_units`:
  - `id`, `product_variant_id` (FK), `name` (e.g. "6-Pack"), `conversion_factor` (INT, min 1), `price` (DECIMAL 10,2), `status` (`active`/`inactive`)
- Base unit is **derived** from `product.measurement_unit` — it is NOT stored in this table
- Stock is always tracked in base units; deduction = `qty_sold × conversion_factor`
- Admin manages sale units per variant from the variant detail area
- Admin-only — `products.manage` permission

## Acceptance Criteria
- [ ] Admin can view all sale units for a variant
- [ ] Admin can create a sale unit with name, conversion factor, price, and status
- [ ] `conversion_factor` must be a positive integer (>= 1)
- [ ] Admin can edit a sale unit's name, conversion factor, price, and status
- [ ] Admin can delete a sale unit (soft or hard delete — team decision)
- [ ] POS displays only `active` sale units
- [ ] Base unit is shown in POS at `conversion_factor = 1` using the product's `measurement_unit.abbreviation`
- [ ] All CUD operations are permission-gated and activity-logged
- [ ] A variant can have multiple sale units; each name unique per variant

## Dependencies
| Dependency | Reason |
|---|---|
| Task 03 (Variants) | `product_variant_id` FK must exist |
| Task 01 (Measurement Units) | Base unit abbreviation for POS display |
| Phase 4 (POS / Sales) | Reads sale units + conversion factor to compute stock deduction |

## Notes
- The base unit itself does not need a `product_variant_sale_units` row — POS constructs it dynamically from `product.measurement_unit` with `conversion_factor = 1`
- If `measurement_unit` is null on the product, POS falls back to "unit" as the label
- `price` on the sale unit is the **sale price for that pack size** — it is independent of the variant's base `price` field
- Conversion factor of `1` is valid (represents the base unit pack size as an explicit sale unit entry, if desired)
