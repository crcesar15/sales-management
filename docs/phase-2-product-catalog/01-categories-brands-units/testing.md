# Testing — Categories, Brands & Measurement Units

## Test File Locations
```
tests/
└── Feature/
    ├── CategoryTest.php
    ├── BrandTest.php
    └── MeasurementUnitTest.php
```

## Coverage Goals
| Area | Target |
|---|---|
| Inertia page rendering | All GET routes return correct component + props |
| Form submissions (CUD) | Redirect on success, session errors on validation failure |
| Authorization | Guest → redirect to login; unauthorized → 403 |
| Soft delete + restore | Full lifecycle |
| Business logic guards | Delete blocked when active products exist |
| Filters (status, search) | Query params reflected in props |

## Key Differences from API Tests

| Aspect | API Test | Inertia Test |
|--------|----------|--------------|
| Create success | `assertStatus(201)` | `assertRedirect(route('categories'))` |
| Update success | `assertStatus(200)` | `assertRedirect(route('categories'))` |
| Delete success | `assertStatus(204)` | `assertRedirect(route('categories'))` |
| Validation error | `assertStatus(422)` + JSON | `assertSessionHasErrors(['field'])` |
| List response | `assertJsonCount(N, 'data')` | `assertInertia(component, props)` |
| Auth denial (GET) | `getJson()` → `assertStatus(403)` | `getJson()` → `assertStatus(403)` |
| Auth denial (CUD) | `postJson()` → `assertStatus(403)` | `post()` → `assertStatus(403)` |

## Feature Test Cases — Categories (`CategoryTest.php`)

**Authorization**
- Guest is redirected to login on GET `/categories`
- User without `categories.view` receives 403 on GET `/categories`
- Admin with permission can access the page (200, Inertia component `Categories/Index`)

**List (GET `/categories`)**
- Returns Inertia page with `Categories/Index` component
- Props include `categories` (paginated) and `filters`
- `?status=archived` returns only soft-deleted records
- `?filter=X` returns filtered results by name
- Default status is `active` (excludes soft-deleted)

**Create (POST `/categories`)**
- Admin creates a category → redirects to `categories.index`, record exists in DB
- Empty name → `assertSessionHasErrors(['name'])`
- Name exceeding 50 chars → `assertSessionHasErrors(['name'])`
- Duplicate name → `assertSessionHasErrors(['name'])`

**Update (PUT `/categories/{category}`)**
- Admin updates name → redirects to `categories.index`, DB reflects new value
- Updating with the same name passes unique rule (ignores self)
- Renaming to an existing name → `assertSessionHasErrors(['name'])`

**Soft Delete (DELETE `/categories/{category}`)**
- Admin deletes category → redirects to `categories.index`, `deleted_at` is set
- Category with active products → redirects back with error flash message

**Restore (PUT `/categories/{category}/restore`)**
- Admin restores soft-deleted category → redirects to `categories.index`, `deleted_at` is null
- Non-admin receives 403 for all CUD actions

## Feature Test Cases — Brands (`BrandTest.php`)

Same structure as Categories, adapted for brands:
- Web routes: `brands`, `brands.store`, `brands.update`, `brands.destroy`, `brands.restore`
- Inertia component: `Brands/Index`
- Validation: name required, max 50 chars, unique
- Delete guard: brand with active products cannot be deleted

## Feature Test Cases — Measurement Units (`MeasurementUnitTest.php`)

Same structure as Categories, adapted for measurement units:
- Web routes: `measurement-units`, `measurement-units.store`, `measurement-units.update`, `measurement-units.destroy`, `measurement-units.restore`
- Inertia component: `MeasurementUnits/Index`
- Validation: name required (max 100), abbreviation required (max 10), both unique
- Delete guard: unit with active products cannot be deleted

**Migration Bug Test**
- Read the migration file content and assert it does NOT contain `'measure_units'` and DOES contain `'measurement_units'` in `down()`

## Test Patterns (Pest)

### Shared Setup
```php
uses()->group('catalog');

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole(RolesEnum::ADMIN);

    $this->salesman = User::factory()->create();
    $this->salesman->assignRole(RolesEnum::SALESMAN);
});
```

### List Page
```php
it('admin can view categories page', function () {
    actingAs($this->admin)
        ->get(route('categories'))
        ->assertStatus(200)
        ->assertInertia(
            fn (Assert $page) => $page
                ->component('Categories/Index')
                ->has('categories')
                ->has('filters')
        );
});
```

### Create (success)
```php
it('admin can create a category', function () {
    actingAs($this->admin)
        ->post(route('categories.store'), ['name' => 'Electronics'])
        ->assertRedirect(route('categories'));

    expect(Category::where('name', 'Electronics')->exists())->toBeTrue();
});
```

### Validation Error
```php
it('validates category name is required', function () {
    actingAs($this->admin)
        ->post(route('categories.store'), ['name' => ''])
        ->assertSessionHasErrors(['name']);
});
```

### Delete with Business Guard
```php
it('cannot delete category with active products', function () {
    $category = Category::factory()->create();
    // attach an active product to the category...

    actingAs($this->admin)
        ->delete(route('categories.destroy', $category))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($category->fresh()->deleted_at)->toBeNull();
});
```

### Restore
```php
it('admin can restore a soft-deleted category', function () {
    $category = Category::factory()->create();
    $category->delete();

    actingAs($this->admin)
        ->put(route('categories.restore', $category->id))
        ->assertRedirect(route('categories'));

    expect($category->fresh()->deleted_at)->toBeNull();
});
```

### Permission Denial
```php
it('non-admin cannot create categories', function () {
    actingAs($this->salesman)
        ->post(route('categories.store'), ['name' => 'Test'])
        ->assertStatus(403);
});

it('non-admin cannot view categories page', function () {
    // Use getJson() to avoid log write permission issues
    actingAs($this->salesman)
        ->getJson(route('categories'))
        ->assertStatus(403);
});
```

### Guest Redirect
```php
it('guest is redirected to login', function () {
    $this->get(route('categories'))
        ->assertRedirect(route('login'));
});
```

## Factory Definitions (reference)
```php
CategoryFactory:        ['name' => fake()->unique()->word()]
BrandFactory:           ['name' => fake()->company()]
MeasurementUnitFactory: ['name' => fake()->word(), 'abbreviation' => fake()->lexify('??')]
```

## Running Tests
```bash
php artisan test tests/Feature/CategoryTest.php
php artisan test tests/Feature/BrandTest.php
php artisan test tests/Feature/MeasurementUnitTest.php
php artisan test --filter=Catalog
php artisan test --group=catalog
```
