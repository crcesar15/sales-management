# Task: Attribute & Variant System

## Goal
Define the structure for Product Variants (Color, Size, Material).

## Technical Implementation

### 1. Database Schema
- [ ] **product_options**:
    -   `id`, `product_id`, `name` (e.g., "Color").
- [ ] **product_option_values**:
    -   `id`, `product_option_id`, `value` (e.g., "Red", "Blue").
- [ ] **product_variants**:
    -   `id`, `product_id`, `sku`, `price`, `stock` (virtual total).
- [ ] **product_variant_option_values**:
    -   `variant_id`, `product_option_value_id`. (Pivot).

### 2. Backend Logic
- [ ] **Models**:
    -   `Product` hasMany `ProductOption`, hasMany `ProductVariant`.
    -   `ProductVariant` belongsToMany `ProductOptionValue`.
- [ ] **SKU Generation**: Logic to append suffix to variant SKUs (e.g., `TSHIRT-RED-L`).

### 3. Frontend Implementation
- [ ] **Product Form (Variants Tab)**:
    -   **Step 1**: Define Options (Add "Color", then add values "Red", "Blue").
    -   **Step 2**: Generate Variants (Cartesian product of options).
    -   **Step 3**: Edit Variant details (Price override, SKU).
