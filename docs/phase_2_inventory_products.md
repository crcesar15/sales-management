# Phase 2: Product & Inventory Structure

**Goal**: Ability to define products, organize them, and track their stock levels across stores.

## 1. Database Schema
- **categories**: `id`, `name`, `slug`, `parent_id`, `image_path` (nullable).
- **brands**: `id`, `name`, `slug`, `logo_path` (nullable).
- **units**: `id`, `name` (Box), `short_name` (bx), `allow_decimal` (boolean).
- **products**:
    - `id`, `name`, `slug`, `sku` (unique), `barcode`, `description`.
    - `category_id`, `brand_id`, `unit_id`.
    - `cost_price` (for reference), `price` (selling price), `tax_rate`.
    - `min_stock_level` (alert threshold).
    - `is_active`.
- **stores**: `id`, `name`, `address`, `phone`, `is_active`.
- **stocks**:
    - `product_id`, `store_id`.
    - `quantity` (decimal/integer).
    - Composite Primary Key `(product_id, store_id)`.

## 2. Backend Implementation
### Models & Relations
- `Product` belongs to `Category`, `Brand`, `Unit`.
- `Product` has many `Stocks`.
- `Store` has many `Stocks`.

### Logic
- **SKU Generation**: Helper to generate unique SKUs if empty.
- **Image Handling**: Use `spatie/laravel-medialibrary` for Product images.
- **Stock Initialization**: When creating a product, optionally initialize 0 stock records for all active stores.

### API / Controllers
- CRUD Resources for `Category`, `Brand`, `Unit`, `Store`.
- **ProductController**: Handle complex creation (uploading images, setting initial attributes).

## 3. Frontend Implementation
### Components
- **ImageUpload**: Reusable drag & drop component.
- **BarcodeGenerator**: Component to render barcode from SKU/Barcode string.

### Pages
- **Products**:
    - `Index`: DataTable with image thumbnails, price, stock summary.
    - `Form`: Multi-tab form (General Info, Pricing, Media, Initial Stock).
- **Inventory/Stocks**:
    - View-only table to see stock levels per store.
    - (Wait for Phase 3/4 for stock transactions).

## 4. Deliverables
- [ ] Catalog setup (Category/Brand/Unit management).
- [ ] Product creation with Images.
- [ ] Multi-store definition.
- [ ] View current stock levels (initially 0).
