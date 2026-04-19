# Testing — Product Variant Units

## Test File Locations
```
tests/
├── Feature/Products/ProductVariantUnitTest.php
└── Unit/Services/Products/ProductVariantUnitServiceTest.php
```

## Coverage Goals
| Area | Target |
|---|---|
| HTTP layer (controller) | All happy paths + auth/permission denials |
| Validation | All rules: required, min:1, unique per variant per type, in:active|inactive, price conditional on type |
| Unique name per variant per type | Same name on different types allowed; same type on same variant blocked |
| Type immutability | Cannot change type after creation |
| Cascade delete | Deleting variant removes all its units |
| Service layer | Create, update, delete, list by type |

## Feature Test Cases (`ProductVariantUnitTest.php`)

**Authorization**
- Guest redirected to login
- User without `products.manage` receives 403 on all actions
- Admin with permission can create, update, delete

**Create — Sale Unit**
- Admin creates sale unit with type=sale, all fields — record in `product_variant_units`
- `type` is required — missing type returns validation error
- `type` must be `sale` or `purchase` — invalid value fails
- `name` is required — missing name returns validation error
- `conversion_factor` must be `>= 1` — value of `0` returns validation error
- `conversion_factor` must be integer — decimal value returns validation error
- `price` is required when type=sale — missing price returns validation error
- `price` must be `>= 0` — negative value returns validation error
- `status` must be `active` or `inactive` — invalid value fails

**Create — Purchase Unit**
- Admin creates purchase unit with type=purchase, no price — record in `product_variant_units`
- `price` is nullable when type=purchase — null or omitted passes validation
- `price` must be null or numeric when type=purchase — string fails

**Name Uniqueness (scoped by variant + type)**
- Duplicate `name` for the **same variant + same type** returns 422
- Same `name` on the **same variant + different type** is allowed (e.g., "Box" as both sale and purchase)
- Same `name` on a **different variant** is allowed (passes)

**Update**
- Admin updates name, price, conversion_factor, status — DB reflects changes
- Updating with same name for own record passes unique check (ignore self)
- Changing name to an existing unit name on the same variant + same type fails (422)
- Type is immutable — attempting to change type returns validation error or is ignored

**Delete**
- Admin deletes unit — record removed from DB (hard delete)
- Delete returns `200` with `"Unit deleted."` message
- Non-admin cannot delete (403)

**Cascade**
- Deleting parent `product_variant` removes all its units (sale + purchase) (`cascadeOnDelete`)
- Deleting parent `product` (via cascade to variants) also removes units

## Unit Test Cases (`ProductVariantUnitServiceTest.php`)

- `create($variant, $data)` inserts record and returns model
- `update($unit, $data)` updates fields and returns fresh model
- `delete($unit)` removes the record from DB
- `list($variant, 'sale')` returns only sale units for the variant
- `list($variant, 'purchase')` returns only purchase units for the variant
- Creating two units on the same variant with different names both succeed
- After `delete()`, `ProductVariantUnit::find($id)` is null

## Factory Notes
```php
// ProductVariantUnitFactory
[
    'product_variant_id' => ProductVariant::factory(),
    'type'               => 'sale',
    'name'               => fake()->word(),
    'conversion_factor'  => fake()->numberBetween(1, 24),
    'price'              => fn (array $attrs) => $attrs['type'] === 'sale'
                                ? fake()->randomFloat(2, 1, 500)
                                : null,
    'status'             => 'active',
    'sort_order'         => 0,
]
```

## POS Compatibility Test (smoke test — Phase 4 prep)
- Variant with 2 active and 1 inactive sale unit → `$variant->activeSaleUnits` returns 2 records
- Sale unit with `status = 'inactive'` is excluded from `activeSaleUnits()` scope
- `$variant->activePurchaseUnits` only returns purchase type units
- `conversion_factor` of `6` with `qty_sold` of `3` → stock deduction = `18` (computed in Phase 4 sales logic)

## Running Tests
```bash
php artisan test tests/Feature/Products/ProductVariantUnitTest.php
php artisan test tests/Unit/Services/Products/ProductVariantUnitServiceTest.php
php artisan test --filter=ProductVariantUnit --coverage
```
