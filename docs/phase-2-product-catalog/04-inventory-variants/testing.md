# Testing — Inventory Variants Module

## Test File Locations
```
tests/
├── Feature/Inventory/InventoryVariantTest.php
└── Unit/Services/VariantServiceTest.php (extend existing)
```

## Coverage Goals
| Area | Target |
|---|---|
| HTTP layer (controller) | index + show happy paths + auth/permission denials |
| Variants list | Filtering by status, searching by product name, pagination |
| Variant detail | Loads with all relations (values, images, units) |
| Simple product variants | Appear in list with is_default=true |
| Authorization | inventory.view for list, inventory.edit for detail |

## Feature Test Cases (`InventoryVariantTest.php`)

**Authorization**
- Guest redirected to login on both index and show
- User without `inventory.view` receives 403 on index
- User without `inventory.edit` receives 403 on show
- User with `inventory.view` can access variant list
- User with `inventory.edit` can access variant detail

**Variants List (index)**
- Returns all variants across products (paginated)
- Simple product default variants appear in list
- Multi-variant products show all their variants
- Filtering by `status=active` returns only active variants
- Filtering by `status=inactive` returns only inactive variants
- Searching by `filter=Product Name` returns matching variants
- Empty filter returns all variants
- Pagination works correctly (per_page parameter)

**Variant Detail (show)**
- Returns variant with product context (name, brand, measurement_unit)
- Returns variant with option values
- Returns variant with images
- Returns variant with sale_units and purchase_units
- 404 when variant does not belong to product
- Simple product variant loads correctly (is_default=true)

**Variant Update (via detail page)**
- User with `inventory.edit` can update identifier, barcode, price, stock, status
- `price` must be >= 0 — negative returns validation error
- `stock` must be integer >= 0 — negative returns validation error
- `status` must be active/inactive/archived — invalid fails
- `identifier` max 50 characters
- `barcode` max 100 characters

## Unit Test Cases (extend existing `VariantServiceTest.php`)

- `listAllVariants()` returns paginated results across products
- `listAllVariants(status: 'active')` filters correctly
- `listAllVariants(filter: 'test')` searches product names
- Simple product default variant included in results

## Factory Notes
Uses existing `ProductVariant::factory()` and `Product::factory()`.

For simple product tests:
```php
$product = Product::factory()->create(['has_variants' => false]);
$variant = $product->variants()->first(); // auto-created default
```

For multi-variant tests:
```php
$product = Product::factory()->hasVariants(3)->create(['has_variants' => true]);
```

## Running Tests
```bash
php artisan test tests/Feature/Inventory/InventoryVariantTest.php
php artisan test --filter=InventoryVariant
```
