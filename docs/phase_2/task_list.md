# Phase 2: Detailed Developer Tasks

## 1. Database Schema

### Task 1.1: Catalog Tables (Categories, Brands, Units)
- **Description**: Create migrations for static data.
- **Steps**:
    1.  `create_categories_table`: `name`, `slug`, `parent_id` (foreign key to categories), `image_path`.
    2.  `create_brands_table`: `name`, `slug`, `logo_path`.
    3.  `create_units_table`: `name` (Box), `short_name` (bx), `allow_decimal` (bool).
    4.  Run migrations.
- **Acceptance Criteria**: Tables exist.

### Task 1.2: Products Table
- **Description**: Main entity table.
- **Steps**:
    1.  `create_products_table`:
        - `name`, `slug`, `sku` (unique, index).
        - `category_id`, `brand_id`, `unit_id` (foreign keys).
        - `cost_price` (decimal 10,2), `price` (decimal 10,2).
        - `min_stock_level` (int).
- **Acceptance Criteria**: Table exists with correct foreign keys.

### Task 1.3: Stores and Stocks Tables
- **Description**: Multi-store inventory system.
- **Steps**:
    1.  `create_stores_table`: `name`, `address`, `phone`.
    2.  `create_stocks_table`:
        - `store_id`, `product_id`.
        - `quantity` (decimal 10,3).
        - Primary Key: `['store_id', 'product_id']`.
- **Acceptance Criteria**: Correct schema for many-to-many relationship with payload (quantity).

## 2. Backend Implementation

### Task 2.1: Models and Relationships
- **Description**: Define Eloquent relationships.
- **Steps**:
    1.  `Category`: `hasMany(Product)`, `belongsTo(Category, 'parent_id')`.
    2.  `Product`: `belongsTo(Category)`, `belongsTo(Brand)`, `hasMany(Stock)`.
    3.  `Store`: `hasMany(Stock)`.
    4.  `Stock`: `belongsTo(Store)`, `belongsTo(Product)`.
- **Acceptance Criteria**: Accessing `$product->stocks` returns collections.

### Task 2.2: Media Library Integration
- **Description**: Handle product images.
- **Steps**:
    1.  Install `spatie/laravel-medialibrary` (if not already detailed in Phase 1 setup).
    2.  Prepare Database: `php artisan vendor:publish` (media tables) -> migrate.
    3.  Add `InteractsWithMedia` trait to `Product`, `Category`, `Brand` models.
- **Acceptance Criteria**: Can attach an image to a product `$product->addMedia($file)->toMediaCollection('images')`.

### Task 2.3: Product Controller
- **Description**: Handle CUD operations.
- **Steps**:
    1.  `store()`:
        - Validation (Unique SKU).
        - Create Product.
        - Upload Image.
        - (Optional) Create 0 quantity records in `stocks` for every active store.
- **Acceptance Criteria**: A product created via API has rows in `stocks` table with 0 qty.

## 3. Frontend Implementation

### Task 3.1: Catalog Management UI
- **Description**: Simple CRUDs for Category, Brand, Unit.
- **Steps**:
    1.  Pages for each entity (Index, Create, Edit).
    2.  Use standard form components.
- **Acceptance Criteria**: Admin can populate the dropdown options for products.

### Task 3.2: Product Form (Advanced)
- **Description**: Validated form for product creation.
- **Steps**:
    1.  `Product/Create.vue`.
    2.  **General Tab**: Name, SKU, Barcode, Description.
    3.  **Pricing Tab**: Cost, Price, Tax.
    4.  **Relationships**: Select Category, Brand, Unit.
    5.  **Media**: Drag & drop zone for images.
- **Acceptance Criteria**: Can save a complex product.

### Task 3.3: Inventory View
- **Description**: Read-only view of stock levels.
- **Steps**:
    1.  `Stocks/Index.vue`.
    2.  Table columns: Product Name, SKU, Store Name, Current Quantity.
- **Acceptance Criteria**: Shows correct 0 values for newly created products.
