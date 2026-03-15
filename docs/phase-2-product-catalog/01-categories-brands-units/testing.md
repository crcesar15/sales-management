# Testing — Categories, Brands & Measurement Units

## Test File Locations
```
tests/
├── Feature/Catalog/
│   ├── CategoryTest.php
│   ├── BrandTest.php
│   └── MeasurementUnitTest.php
└── Unit/Services/Catalog/
    ├── CategoryServiceTest.php
    ├── BrandServiceTest.php
    └── MeasurementUnitServiceTest.php
```

## Coverage Goals
| Area | Target |
|---|---|
| HTTP layer (controller) | All happy paths + auth/permission denials |
| Service business logic | All branches (list, create, update, delete guard, restore) |
| Validation rules | Every rule: required, max length, unique (create & update) |
| Soft delete + restore | Full lifecycle |
| Activity log | Entry created on create / update / delete |

## Feature Test Cases — Categories (`CategoryTest.php`)

**Authorization**
- Guest is redirected to login
- User without `products.manage` receives 403
- Admin with permission can access the page

**List**
- Returns paginated results (15/page)
- Search by name filters correctly
- Soft-deleted records excluded from default list
- `trashed=true` returns only soft-deleted records

**Create**
- Admin creates a category — record exists in DB
- Duplicate name returns 422 with `name` error
- Empty name returns validation error
- Name exceeding 50 chars returns validation error
- Activity log entry created on successful create

**Update**
- Admin updates name — DB reflects new value
- Updating with the same name passes unique rule (ignores self)
- Renaming to an existing name returns 422

**Soft Delete**
- Admin deletes category — `deleted_at` is set
- Category with active products cannot be deleted (returns 422)
- Category can be deleted after all its products are soft-deleted

**Restore**
- Admin restores soft-deleted category — `deleted_at` is null
- Non-admin cannot perform any CUD action (403)

## Feature Test Cases — Measurement Units (`MeasurementUnitTest.php`)

- Admin creates unit with `name` + `abbreviation` — record in DB
- Missing `name` or `abbreviation` returns validation error
- Name exceeding 100 chars fails; abbreviation exceeding 10 chars fails
- Unit in use by active product cannot be deleted (422)
- Unit not in use can be soft-deleted and restored

**Migration Bug Test**
- Read the migration file content and assert it does NOT contain `'measure_units'` and DOES contain `'measurement_units'` in `down()`

## Unit Test Cases — CategoryService (`CategoryServiceTest.php`)

- `list([])` returns paginated results ordered by name
- `list(['search' => 'X'])` filters correctly
- `list(['trashed' => true])` returns only deleted records
- `create(['name' => 'X'])` persists and returns the model
- `update($category, ['name' => 'Y'])` changes the name
- `delete($category)` throws `HttpResponseException` when active products exist
- `delete($category)` soft-deletes when no active products
- `restore($id)` clears `deleted_at`

## Factory Definitions (reference)
```php
CategoryFactory:        ['name' => fake()->unique()->word()]
BrandFactory:           ['name' => fake()->company()]
MeasurementUnitFactory: ['name' => fake()->word(), 'abbreviation' => fake()->lexify('??')]
```

## Running Tests
```bash
php artisan test tests/Feature/Catalog/
php artisan test tests/Unit/Services/Catalog/
php artisan test --filter=Catalog --coverage
```
