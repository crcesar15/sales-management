# Testing — Product Variants & Options

## Test File Locations
```
tests/
├── Feature/Products/
│   ├── ProductOptionsTest.php
│   ├── ProductVariantGenerateTest.php
│   └── ProductVariantCrudTest.php
└── Unit/Services/Products/
    └── ProductVariantServiceTest.php
```

## Coverage Goals
| Area | Target |
|---|---|
| Option CRUD | Add, rename, delete (with guard) |
| Option value CRUD | Add, delete (with guard if variant uses it) |
| Auto-generate | Correct combination count, creates correct pivot rows |
| Auto-generate guard | Blocked when variants already exist |
| Manual variant add | Creates variant with correct option_value pivot |
| Duplicate combination | Second identical combination rejected |
| Variant update | Price, stock, status, identifier changes persist |
| Identifier uniqueness | Duplicate non-null identifier rejected; two NULLs allowed |
| Permission gates | 403 for non-admin on all routes |

## Feature Test Cases — Options (`ProductOptionsTest.php`)

**Options**
- Admin adds an option to a product — record in `product_options`
- Admin renames an option — name updated in DB
- Admin deletes an option with no values — removed
- Option with values can be deleted (cascade deletes values)

**Option Values**
- Admin adds a value to an option — record in `product_option_values`
- Admin deletes a value not used by any variant — removed
- Admin cannot delete a value used by an existing variant (422)

## Feature Test Cases — Auto-generate (`ProductVariantGenerateTest.php`)

- 2 options × 3 values = 6 variants created, each with correct pivot rows
- Default price is `0.00`, default stock is `0` for all generated variants
- Generated variants have `status = 'active'`
- Each combination is unique — pivot table has exactly `6 × 2` rows (6 variants × 2 option values each)
- Calling generate when variants already exist returns 422
- Missing `options` array returns validation error
- Option with no values returns validation error

## Feature Test Cases — Manual Variant CRUD (`ProductVariantCrudTest.php`)

- Admin adds variant with valid `option_value_ids` — variant + pivot rows created
- Duplicate `option_value_ids` combination for same product returns 422
- `identifier` must be unique — duplicate identifier returns validation error
- Two variants with `null` identifier are both valid (partial unique index)
- `price` must be `>= 0`; `stock` must be `>= 0`
- Admin updates variant price, stock, status — DB reflects changes
- Admin deletes variant — record removed (hard delete or status=archived per team preference)

## Unit Test Cases — ProductVariantService (`ProductVariantServiceTest.php`)

- `cartesian([['R','B'], ['S','M']])` returns `[['R','S'],['R','M'],['B','S'],['B','M']]`
- `generateVariants()` throws `HttpResponseException` when product has existing variants
- `generateVariants()` wraps creation in a transaction — partial failure rolls back
- `isDuplicateCombination()` returns `true` for identical value sets, `false` otherwise
- `storeManual()` creates variant and syncs pivot
- `update()` changes only supplied fields

## Factory Notes
```php
// ProductOptionFactory
['product_id' => Product::factory(), 'name' => 'Color']

// ProductOptionValueFactory
['product_option_id' => ProductOption::factory(), 'value' => 'Red']

// ProductVariantFactory
['product_id' => Product::factory(), 'price' => 9.99, 'stock' => 10, 'status' => 'active']
```

## Running Tests
```bash
php artisan test tests/Feature/Products/ProductVariant*
php artisan test tests/Unit/Services/Products/ProductVariantServiceTest.php
```
