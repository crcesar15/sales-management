# Task: Product Management (CRUD)

## Goal
Main interface for creating and managing products (Simple & Variable).

## Technical Implementation

### 1. Database Schema
- [ ] **products**:
    -   `id`, `name`, `slug`, `sku` (nullable), `barcode` (nullable), `description`.
    -   `category_id` (belongsToMany if multiple?), `brand_id`, `unit_id`.
    -   `price`, `cost_price`, `min_stock_level`.
    -   `is_variant` (boolean) or check `product_variants` count.

### 2. Backend Logic
- [ ] **Barcode Logic**:
    -   If `barcode` is empty on save, generate one using `EAN-13` or internal format (e.g., `20000000001`).
- [ ] **Category Logic**:
    -   Pivot table `category_product` for multiple categories.
- [ ] **Controller**: `ProductController@store`
    -   Handle simple product creation.
    -   Handle variable product creation (delegating to Variant logic).

### 3. Frontend Implementation
- [ ] **Product List**:
    -   Show thumbnail, Name, SKU, Stock (Sum of all stores/variants), Price.
- [ ] **Product Form**:
    -   **Tabs**: Info, Media, Pricing, Inventory, Variants.
    -   **Barcode Field**: "Auto-generate" checkbox.
