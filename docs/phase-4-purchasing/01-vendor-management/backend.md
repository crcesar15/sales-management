# Task 01: Vendor Management — Backend

## Implementation Steps

1. **Migration** — create `vendors` table with all columns; add `index('status')`, `index('fullname')`, `unique('email')`
2. **Model** — `App\Models\Vendor`; cast `additional_contacts` and `meta` to `array`; define `hasMany` for `purchaseOrders` and `catalogEntries`
3. **Form Request** — `StoreVendorRequest` / `UpdateVendorRequest`; validate `status` enum, unique email (ignore on update), `additional_contacts` array shape
4. **Resource** — `VendorResource` transforming model to JSON; include `additional_contacts` array
5. **Controller** — `VendorController` (resource); gate all methods with `vendors.manage`
6. **Deletion Guard** — in `destroy()`, check `$vendor->purchaseOrders()->exists()` and `$vendor->catalogEntries()->exists()`; return 409 if true
7. **Service (optional)** — `VendorService::delete(Vendor $vendor)` encapsulates guard + delete
8. **Routes** — `Route::apiResource('vendors', VendorController::class)->middleware('permission:vendors.manage')`
9. **Activity Log** — log create, update, delete actions via `LogsActivity` trait or observer

## Key Classes / Files

| File                                          | Purpose                         |
|-----------------------------------------------|---------------------------------|
| `database/migrations/..._create_vendors_table`| Schema                          |
| `app/Models/Vendor.php`                       | Eloquent model                  |
| `app/Http/Controllers/VendorController.php`   | CRUD controller                 |
| `app/Http/Requests/StoreVendorRequest.php`    | Validation                      |
| `app/Http/Resources/VendorResource.php`       | API transformer                 |

## Important Patterns

```php
// Deletion guard
if ($vendor->purchaseOrders()->exists() || $vendor->catalogEntries()->exists()) {
    abort(409, 'Vendor has associated records and cannot be deleted.');
}

// Unique email ignoring current record
'email' => ['nullable', 'email', Rule::unique('vendors')->ignore($this->vendor)]
```

## Packages
- `spatie/laravel-permission` — `permission:vendors.manage` middleware
- `spatie/laravel-activitylog` — audit trail on model changes

## Gotchas
- `email` uniqueness: `unique` constraint at DB level but column is nullable — two NULL emails are allowed by most DBs
- Enforce uniqueness only when email is present using a conditional unique rule
- Cast `additional_contacts` to `array`; UI sends it as a JSON array string in some edge cases — normalize in the request
