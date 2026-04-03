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

## Known Issues

- `storage/logs/laravel.log` has permission issues — use `getJson()` instead of `get()` for forbidden assertions to avoid log write failures
