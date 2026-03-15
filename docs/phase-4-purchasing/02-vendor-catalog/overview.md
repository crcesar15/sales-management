# Task 02: Vendor Catalog — Overview

## What
A catalog linking vendors to product variants with purchasing terms (price, unit, MOQ, lead time).

## Why
Before a purchase order can be created, the system must know which variants a vendor supplies and at what price/unit. The catalog is the single source of truth for this.

## Requirements
- Link a vendor to a product variant with pricing and purchasing terms
- New columns added to existing `catalog` table via migration
- `purchase_unit_id` — the unit in which the vendor sells (e.g., "box")
- `conversion_factor` — how many base stock units are in one purchase unit (e.g., 1 box = 12 units)
- `minimum_order_quantity` — minimum units that must be ordered
- `lead_time_days` — typical delivery lead time in days
- Unique constraint: one entry per `vendor_id` + `product_variant_id`
- Status `active/inactive` controls visibility when creating purchase orders
- Admin-only; requires `vendors.manage` permission

## Acceptance Criteria
- [ ] Migration adds four new columns to `catalog` table without breaking existing data
- [ ] Catalog entries can be created, edited, and deactivated
- [ ] Only `active` catalog entries appear when building a purchase order
- [ ] Unique constraint prevents duplicate vendor+variant combinations
- [ ] `conversion_factor` defaults to 1 when no purchase unit is set
- [ ] Deleting a product variant or vendor is blocked if catalog entries exist

## Dependencies
- `vendors` table (Task 01)
- `product_variants` table (Phase 1)
- `measurement_units` table (Phase 1)
- Phase 4 Task 03 (Purchase Orders) reads this table for line item data

## Notes
- Price in catalog = default price; PO snapshots the price at creation time
- `conversion_factor` must be a positive integer ≥ 1
- Deactivating an entry does not affect existing POs
