# Task: Product Management (CRUD)

## Goal
Main interface for creating and managing products (Simple & Variable).

## Technical Implementation

### 1. Database Schema
- [ ] **products**:
    -   `id`, `name`, `slug`, `sku` (nullable), `barcode` (nullable), `description`.
    -   `brand_id` (FK), `unit_id` (FK).
    -   `price` (decimal), `cost_price` (decimal), `min_stock_level` (int).
    -   `is_variant` (boolean default false).
- [ ] **category_product** (Pivot):
    -   `product_id`, `category_id`.

### 2. Backend Logic
- [ ] **Barcode Logic**:
    -   Observer `creating`: If `barcode` is empty -> `EAN13::generate()`.
- [ ] **ProductController**:
    -   `store(Request $request)`:
        -   Wrap in `DB::transaction`.
        -   Create Product. Attach Categories.
        -   Handle Media (Images).
        -   If request has `variants` array -> call Variant Service to create variants.
        -   If no variants -> Create 1 default `Stock` entry for each store (0 qty).

### 3. Frontend Implementation
- [ ] **Product Form**:
    -   **Multi-step or Tabs**:
        1.  **Basic**: Name, Slug, Brand, Unit, Category (MultiSelect).
        2.  **Pricing**: Price, Cost, Tax.
        3.  **Inventory**: SKU, Barcode, Alert Level.
        4.  **Variants**: (See Task 2 Component).
        5.  **Images**: Spatie Media Library uploader.

### 4. Verification
-   Create Simple Product "Apple" (Auto barcode).
-   Create Variable Product "Sneakers" with Sizes.
-   Ensure "Sneakers" itself doesn't have stock, but its variants do.
