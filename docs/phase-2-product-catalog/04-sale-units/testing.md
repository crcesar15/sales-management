# Testing — Sale Units

## Test File Locations
```
tests/
├── Feature/Products/SaleUnitTest.php
└── Unit/Services/Products/SaleUnitServiceTest.php
```

## Coverage Goals
| Area | Target |
|---|---|
| HTTP layer (controller) | All happy paths + auth/permission denials |
| Validation | All rules: required, min:1, unique per variant, in:active|inactive |
| Unique name per variant | Same name on different variants is allowed; same variant blocked |
| Cascade delete | Deleting variant removes its sale units |
| Service layer | Create, update, delete |

## Feature Test Cases (`SaleUnitTest.php`)

**Authorization**
- Guest redirected to login
- User without `products.manage` receives 403 on all actions
- Admin with permission can create, update, delete

**Create**
- Admin creates sale unit with all fields — record in `product_variant_sale_units`
- `name` is required — missing name returns validation error
- `conversion_factor` must be `>= 1` — value of `0` returns validation error
- `conversion_factor` must be integer — decimal value returns validation error
- `price` must be `>= 0` — negative value returns validation error
- `status` must be `active` or `inactive` — invalid value fails
- Duplicate `name` for the **same variant** returns 422
- Same `name` on a **different variant** is allowed (passes)

**Update**
- Admin updates name, price, conversion_factor, status — DB reflects changes
- Updating with same name for own record passes unique check (ignore self)
- Changing name to an existing sale unit name on the same variant fails (422)

**Delete**
- Admin deletes sale unit — record removed from DB (hard delete)
- Delete returns `200` with `"Sale unit deleted."` message
- Non-admin cannot delete (403)

**Cascade**
- Deleting parent `product_variant` removes all its sale units (`cascadeOnDelete`)
- Deleting parent `product` (via cascade to variants) also removes sale units

## Unit Test Cases (`SaleUnitServiceTest.php`)

- `create($variant, $data)` inserts record and returns model
- `update($saleUnit, $data)` updates fields and returns fresh model
- `delete($saleUnit)` removes the record from DB
- Creating two sale units on the same variant with different names both succeed
- After `delete()`, `ProductVariantSaleUnit::find($id)` is null

## Factory Notes
```php
// ProductVariantSaleUnitFactory
[
    'product_variant_id' => ProductVariant::factory(),
    'name'               => fake()->word(),
    'conversion_factor'  => fake()->numberBetween(1, 24),
    'price'              => fake()->randomFloat(2, 1, 500),
    'status'             => 'active',
]
```

## POS Compatibility Test (smoke test — Phase 4 prep)
- Variant with 2 active and 1 inactive sale unit → `$variant->activeSaleUnits` returns 2 records
- Sale unit with `status = 'inactive'` is excluded from `activeSaleUnits()` scope
- `conversion_factor` of `6` with `qty_sold` of `3` → stock deduction = `18` (computed in Phase 4 sales logic)

## Running Tests
```bash
php artisan test tests/Feature/Products/SaleUnitTest.php
php artisan test tests/Unit/Services/Products/SaleUnitServiceTest.php
php artisan test --filter=SaleUnit --coverage
```
