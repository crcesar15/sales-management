# Task: Attribute & Variant System

## Goal
Define the structure for Product Variants (Color, Size, Material).

## Technical Implementation

### 1. Database Schema
- [ ] **product_options**:
    -   `id`, `product_id` (FK), `name` (e.g., "Color").
- [ ] **product_option_values**:
    -   `id`, `product_option_id` (FK), `value` (e.g., "Red", "Blue").
- [ ] **product_variants**:
    -   `id`, `product_id` (FK), `sku` (nullable), `price` (decimal, nullable), `stock` (virtual calculated field).
- [ ] **product_variant_option_values**:
    -   `variant_id` (FK to product_variants), `product_option_value_id` (FK to product_option_values).
    -   Unique composite key to prevent duplicate variants.

### 2. Backend Logic
- [ ] **Models**:
    -   `Product` hasMany `ProductOption`.
    -   `ProductOption` hasMany `ProductOptionValue`.
    -   `Product` hasMany `ProductVariant`.
    -   `ProductVariant` belongsToMany `ProductOptionValue` (pivot).
- [ ] **Variant Generation Service**:
    -   Input: Arrays of options (Color: [R, B], Size: [S, M]).
    -   Output: Cartesian Product (R-S, R-M, B-S, B-M).

### 3. Frontend Implementation
- [ ] **Variant Manager Component**:
    -   Button "Add Option". Input "Option Name".
    -   Tags Input for "Option Values".
    -   "Generate Variants" button.
    -   Table displaying generated variants with editable SKU/Price fields.

### 4. Verification
-   Create Product "T-Shirt". Add Option "Color" (Red, Blue).
-   Generate 2 Variants.
-   Verify DB has correct rows in pivot tables.
