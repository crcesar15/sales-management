# Phase 2: Product & Inventory Structure

**Goal**: Ability to define products (Simple & Variable), organize them in a catalog, and track their stock levels across multiple stores with audit capabilities.

## 1. Database Schema
### Catalog
- **categories**: `id`, `name`, `slug`, `parent_id`, `image_path`.
- **brands**: `id`, `name`, `slug`, `logo_path`.
- **units**: `id`, `name` (Box), `short_name` (bx), `allow_decimal` (boolean).

### Products & Variants
- **products**:
    - `id`, `name`, `slug`, `sku`, `barcode`, `description`.
    - `brand_id`, `unit_id`.
    - `price`, `cost_price`.
    - `min_stock_level`.
    - `is_variant` (bool).
- **product_options**: `id`, `product_id`, `name` (Color).
- **product_option_values**: `id`, `product_option_id`, `value` (Red).
- **product_variants**: `id`, `product_id`, `sku`, `price`.
- **product_variant_option_values**: Pivot (Variant <-> OptionValue).
- **category_product**: Pivot (Product <-> Category).

### Inventory
- **stores**: `id`, `name`, `address`, `phone`, `is_active`.
- **stocks**:
    - `store_id`, `product_id`, `variant_id`.
    - `quantity` (decimal).
- **stock_adjustments**: `id`, `reference_no`, `store_id`, `reason`.
- **stock_adjustment_items**: `adjustment_id`, `product_id`, `variant_id`, `quantity`.

## 2. Backend Implementation (Laravel)
- **Logics**:
    - **Auto-Barcode**: Generate EAN13 if empty.
    - **Stock Init**: Create 0-stock records on product creation.
    - **Transactional**: Strict DB transactions for all inventory changes.

## 3. Frontend Implementation (Vue 3)
- **Catalog**: Tree view for Categories.
- **Product Form**:
    - Multi-step wizard or Tabs.
    - **Variant Generator**: UI to define options and generate variant rows.
- **Inventory**:
    - Stock Overview Table.
    - **Adjustment Form**: Manual Add/Subtract stock with reason codes.

## 4. Deliverables
- [ ] Catalog Management (Categories/Brands/Units).
- [ ] Product CRUD (Simple + Variable Support).
- [ ] Image Upload (Spatie Media Library).
- [ ] Multi-Store Stock Tracking.
- [ ] Stock Adjustments (Damage/Loss/Opening Stock).
