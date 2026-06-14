# Testing Rules

## Framework

- Pest 3 with Laravel plugin — never PHPUnit-style tests
- Create tests with `php artisan make:test --pest {name}` (feature test by default)
- Add `--unit` for unit tests — most tests should be feature tests

## Running Tests

```bash
php artisan test --compact                           # All tests
php artisan test --compact --filter=testName         # Single test
php artisan test --compact --filter=UsersTest        # Single file
```

## Test Conventions

- Use `RefreshDatabase` trait for database tests
- Create models via factories: `User::factory()->create()`
- Check for factory custom states before manually setting attributes
- Use `fake()` or `$this->faker` for test data — follow existing conventions in the test file
- For API tests, use `getJson()`, `postJson()`, `putJson()`, `deleteJson()` (not `get()`, `post()`)

## Seeder Dependencies

- Pest auto-seeds `RoleSeeder` and `PermissionSeeder` before every test (configured in `tests/Pest.php`)
- When creating test users, assign permissions explicitly: `$user->givePermissionTo(PermissionsEnum::X)`
- When testing authorization, create a user and assign only the permissions needed for the test

## Factory Conventions

- All factories are `final class` with `@extends Factory<Model>` PHPDoc annotation
- Use enum values in factories: `'status' => SalesOrderStatus::DRAFT->value`
- Use nested factory calls for required relationships: `'customer_id' => Customer::factory()`
- Use factory states for specific scenarios (e.g., `User::factory()->inactive()->create()`)

## Known Issues

- `storage/logs/laravel.log` has permission issues — use `getJson()` instead of `get()` for forbidden assertions to avoid log write failures