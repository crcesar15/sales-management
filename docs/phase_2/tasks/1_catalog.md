# Task: Catalog Management

## Goal
Manage the static data structure for products: Categories, Brands, and Measurement Units.

## Technical Implementation

### 1. Database Schema
- [ ] **Categories**:
    -   `id`, `name`, `slug` (unique), `parent_id` (nullable, foreign key to categories), `image_path` (nullable).
    -   `created_at`, `updated_at`, `deleted_at` (SoftDeletes).
- [ ] **Brands**:
    -   `id`, `name`, `slug` (unique), `website` (nullable), `logo_path` (nullable).
    -   `created_at`, `updated_at`, `deleted_at` (SoftDeletes).
- [ ] **Units**:
    -   `id`, `name` (e.g., Kilogram), `short_name` (kg), `allow_decimal` (boolean, default true).
    -   `created_at`, `updated_at`.

### 2. Backend Logic
- [ ] **Models**: `Category`, `Brand`, `Unit`. All use `HasFactory`, `SoftDeletes` (except Unit maybe).
- [ ] **Requests**: `StoreCategoryRequest` (name required, unique slug logic).
- [ ] **Media**: Use `InteractsWithMedia` on Category and Brand models.
- [ ] **API Resources**: `CategoryResource` (include children recursively if needed), `BrandResource`.

### 3. Frontend Implementation
- [ ] **Pages**:
    -   `Categories/Index.vue`: TreeTable or Nested List.
    -   `Brands/Index.vue`: simple List with Logo.
    -   `Units/Index.vue`: simple Table.
- [ ] **Components**:
    -   `ImageUpload.vue`: Reusable component for uploading category/brand images.

### 4. Verification
-   Create "Electronics" -> "Computers" -> "Laptops" category hierarchy.
-   Create "Samsung" brand with logo.
-   Create "Pcs" unit (no decimals).
