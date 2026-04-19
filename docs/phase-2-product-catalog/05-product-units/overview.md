# Task 05 — Product Variant Units

## What
Product variant units define the different packaging/quantities in which a variant can be **sold** (sale units) or **purchased** (purchase units). They share a single table with a `type` discriminator.

**Example:** A variant "Mineral Water 500ml" has base unit "Piece" (from the product's measurement unit).

**Sale units** (for POS):
- **Bottle** → `conversion_factor = 1`, `price = $1.50`
- **6-Pack** → `conversion_factor = 6`, `price = $8.00`
- **Crate of 24** → `conversion_factor = 24`, `price = $28.00`

**Purchase units** (for purchasing from vendors):
- **Small Box** → `conversion_factor = 6` (no price — vendor cost goes on catalog)
- **Big Box** → `conversion_factor = 24`

The POS displays all active sale units. When a sale unit is selected, stock is deducted by `qty_sold × conversion_factor`. Purchase units are referenced by the vendor catalog (phase-4) to compute stock additions.

## Why
- **Sale units:** Retail stores sell the same product in multiple packaging sizes at different price points. Tracking stock in a single base unit while selling in various pack sizes requires a conversion layer.
- **Purchase units:** Vendors supply products in various packaging sizes. A single vendor may offer the same variant in a small box (6 units) or a big box (24 units). Defining purchase units per variant allows the vendor catalog to reference them with vendor-specific pricing.

Using one table for both avoids duplication — the structure (name, conversion_factor, status) is identical. The only difference is that sale units include a `price` field (selling price), while purchase units don't (cost price is per vendor, stored on the catalog table).

## Requirements
- **New table** `product_variant_units`:
  - `id`, `product_variant_id` (FK), `type` (`sale`/`purchase`), `name` (e.g. "6-Pack"), `conversion_factor` (INT, min 1), `price` (DECIMAL 10,2, nullable — required for sale, null for purchase), `status` (`active`/`inactive`), `sort_order` (INT, default 0)
- Base unit is **derived** from `product.measurement_unit` — it is NOT stored in this table
- Stock is always tracked in base units; deduction = `qty_sold × conversion_factor`
- Admin manages sale and purchase units from the **Inventory > Variants > Variant Detail > Units** tab (see Task 04)
- Admin-only — `inventory.edit` permission for management; `inventory.view` for viewing
- Name must be unique per variant **per type** (allows "Box" as both a sale and purchase unit)

## Acceptance Criteria
- [ ] Admin can view all sale and purchase units for a variant
- [ ] Admin can create a sale unit with name, conversion factor, price, and status
- [ ] Admin can create a purchase unit with name, conversion factor, and status (no price)
- [ ] `conversion_factor` must be a positive integer (>= 1)
- [ ] `price` is required for sale type, nullable for purchase type
- [ ] Admin can edit a unit's name, conversion factor, price (sale only), and status
- [ ] Admin can delete a unit (hard delete)
- [ ] POS displays only `active` sale units + the derived base unit
- [ ] Base unit is shown in POS at `conversion_factor = 1` using the product's `measurement_unit.abbreviation`
- [ ] Products with a single presentation work without any variant unit records (base unit is derived dynamically)
- [ ] All CUD operations are permission-gated and activity-logged
- [ ] A variant can have multiple units per type; each name unique per variant per type

## Dependencies
| Dependency | Reason |
|---|---|
| Task 03 (Variants) | `product_variant_id` FK must exist |
| Task 01 (Measurement Units) | Base unit abbreviation for POS display |
| Phase 4 (Vendor Catalog) | References purchase units for vendor-specific pricing |
| Phase 4 (POS / Sales) | Reads sale units + conversion factor to compute stock deduction |
| Task 04 (Inventory Variants) | UI for managing variant units |

## Notes
- The base unit itself does not need a `product_variant_units` row — POS constructs it dynamically from `product.measurement_unit` with `conversion_factor = 1`
- If `measurement_unit` is null on the product, POS falls back to "Unit" as the label and "pc" as abbreviation
- `price` on sale units is the **sale price for that pack size** — it is independent of the variant's base `price` field
- Purchase units have no `price` — the vendor cost is stored on the `catalog` table (phase-4) per vendor-variant-unit combination
- `measurement_unit_id` on `products` defines the **base unit** (atomic stock unit). All conversion factors in this table are relative to it. No changes needed to the per-product measurement_unit setup.
- Conversion factor of `1` is valid (represents the base unit pack size as an explicit entry, if desired alongside the dynamic base unit)
- `sort_order` controls display ordering in POS and admin UI; defaults to 0
