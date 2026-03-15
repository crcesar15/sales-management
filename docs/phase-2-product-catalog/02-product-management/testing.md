# Testing — Product Management

## Test File Locations
```
tests/
├── Feature/Products/
│   ├── ProductListTest.php
│   ├── ProductCrudTest.php
│   ├── ProductMediaTest.php
│   └── ProductStatusTest.php
└── Unit/Services/Products/
    └── ProductServiceTest.php
```

## Coverage Goals
| Area | Target |
|---|---|
| List filtering | All filter params: search, brand, category, status, trashed |
| CRUD happy paths | Create, read, update, delete, restore |
| Validation | Every rule: required, max lengths, enum, FK existence (not trashed) |
| Deletion guard | Blocked with active/inactive variants; allowed with archived-only |
| Pending media | Upload, commit on product save, removal, file type/size rejection |
| Policy | Allow + deny for each action |
| Activity log | Entry on create, update, delete |

## Feature Test Cases — List (`ProductListTest.php`)

- Guest redirected to login
- User without `products.manage` gets 403
- Admin sees Inertia `Products/Index` component
- Results paginated at 20/page by default
- Search by name filters correctly (partial match)
- Filter by `brand_id` returns only matching products
- Filter by `category_id` returns only products in that category
- Filter by `status` returns only matching products
- Soft-deleted products excluded from default list
- `trashed=true` returns only soft-deleted products

## Feature Test Cases — CRUD (`ProductCrudTest.php`)

**Create**
- Admin creates product with all fields — DB record + category pivot rows exist
- `name` required, fails if empty
- `description` max 350 chars — 351 chars fails validation
- `status` must be `active|inactive|archived` — invalid value fails
- `brand_id` must reference non-trashed brand — trashed brand ID fails
- Activity log entry created on successful create

**Update**
- Admin updates `name` and `status` — DB reflects changes
- Category sync: sending `category_ids: [2]` replaces old `[1]` association
- Updating with same values is a no-op (no dirty log entry)

**Delete**
- Admin soft-deletes product — `deleted_at` is set
- Product with active variants cannot be deleted (422)
- Product with only archived variants can be deleted
- Deletion message is `"Product deleted successfully."`

**Restore**
- Admin restores soft-deleted product — `deleted_at` is null
- Restore response contains `"Product restored successfully."`

## Feature Test Cases — Media (`ProductMediaTest.php`)

- Admin uploads image → 201 with `uuid` and `url`
- Non-image file (PDF) rejected
- File > 5MB rejected
- Admin deletes pending upload → temp directory removed from disk
- Creating product with `pending_media_uuids` → media committed to `images` collection
- Updating product with `remove_media_ids` → specified media deleted

## Unit Test Cases — ProductService (`ProductServiceTest.php`)

- `list()` returns results ordered by name, excludes soft-deleted by default
- `list(['trashed' => true])` returns only deleted
- `create()` stores product and syncs categories
- `update()` changes fields and re-syncs categories
- `delete()` throws `HttpResponseException` when active variants exist
- `delete()` soft-deletes when only archived variants exist
- `restore()` clears `deleted_at`

## Factory Notes
```php
// ProductFactory
['name' => fake()->words(3, true), 'status' => 'active', 'brand_id' => null]
```
Use `Product::factory()->for(Brand::factory())->create()` for brand association.

## Running Tests
```bash
php artisan test tests/Feature/Products/
php artisan test tests/Unit/Services/Products/
php artisan test --filter=Product --coverage
```
