# Task: Catalog Management

## Goal
Manage the static data structure for products: Categories, Brands, and Measurement Units.

## Technical Implementation

### 1. Database Schema
- [ ] **Categories**:
    -   `id`, `name`, `slug`, `parent_id` (nullable), `image_path` (nullable).
    -   Self-referencing relationship for hierarchy.
- [ ] **Brands**:
    -   `id`, `name`, `slug`, `website` (nullable), `logo_path` (nullable).
- [ ] **Units**:
    -   `id`, `name` (e.g., Kilogram), `short_name` (kg), `allow_decimal` (boolean).

### 2. Backend Logic
- [ ] **Controllers**: `CategoryController`, `BrandController`, `UnitController`.
- [ ] **Requests**: Validation for unique names/slugs.
- [ ] **Media**: Use `Spatie\MediaLibrary` for Category/Brand images.

### 3. Frontend Implementation
- [ ] **Components**:
    -   `SimpleResouceCrud.vue`: A reusable generic CRUD component for Brands/Units to save time.
    -   `CategoryTree.vue`: Visualize parent-child relationships.
