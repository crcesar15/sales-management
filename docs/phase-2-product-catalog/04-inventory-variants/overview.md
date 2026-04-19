# Task 04 — Inventory Variants Module

## What
A dedicated **Inventory > Variants** section in the sidebar that lists all product variants across the catalog and provides a **Variant Detail** page for managing all variant-level operations: details, images, and units.

## Why
The product edit page currently handles too many concerns: product info, images, options, variants (CRUD, images, editing), and now variant units. This makes the page complex and hard to maintain.

Following the Shopify pattern, variant-level operations should live in a separate Inventory section:
- **Product edit** focuses on product-level setup: name, description, options, and basic variant creation
- **Inventory > Variants** handles everything after a variant exists: details, images, sale/purchase units

Additionally, **simple products** (no variants) have a hidden default variant that currently cannot access variant-level features. The inventory list makes these discoverable.

## Requirements
- New sidebar item "Variants" under the existing Inventory group
- New permission `inventory.view` and `inventory.edit`
- **Variants List page** (`/inventory/variants`) — flat list of all variants across products
- **Variant Detail page** (`/products/{product}/variants/{variant}`) — tabbed management
- Product edit page simplification — variant dialogs replaced with "Manage" navigation

## Variant Detail Page Tabs

| Tab | Purpose | Editable Fields |
|-----|---------|----------------|
| Details | Variant info | identifier, barcode, price, stock, status |
| Images | Variant images | Image assignment from product media |
| Units | Sale + purchase units | Name, conversion_factor, price (sale only), status, sort_order |

Note: A **Vendors** tab (supplier catalog) will be added in phase-4 when the vendor catalog module is implemented.

## Acceptance Criteria
- [ ] Sidebar shows Inventory > Variants (permission-gated with `inventory.view`)
- [ ] Variants list shows all variants including simple product default variants
- [ ] List supports filtering by status and searching by product name
- [ ] Clicking a variant navigates to the Variant Detail page
- [ ] Details tab allows editing identifier, barcode, price, stock, status
- [ ] Images tab allows assigning product media to a variant
- [ ] Units tab allows CRUD for sale and purchase units
- [ ] Product edit page shows "Manage" button per variant → navigates to detail page
- [ ] Simple product edit page shows "Manage Inventory" link → navigates to default variant detail
- [ ] All operations are permission-gated and activity-logged

## Dependencies
| Dependency | Reason |
|---|---|
| Task 03 (Variants) | `product_variants` table must exist |
| Task 05 (Product Units) | Units tab reads/writes `product_variant_units` |
| Task 01 (Measurement Units) | Base unit display in Units tab |

## Notes
- Variant **creation** and **deletion** stay on the product edit page (tightly coupled to options structure)
- Variant **detail management** moves to the Inventory module
- The variant detail page URL `/products/{product}/variants/{variant}` keeps REST semantics
- Simple product default variants appear with a "Default" tag in the list (no option values)
- `inventory.view` gates the list page; `inventory.edit` gates variant detail editing
- The existing product permissions (`product.view`, `product.edit`) remain for the Products section
