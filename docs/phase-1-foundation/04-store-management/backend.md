# Store Management — Backend

## File Map

| File | Responsibility |
|---|---|
| `app/Models/Store.php` | Eloquent model with `InteractsWithMedia`, `BelongsToMany` to users, logo URL accessors |
| `app/Http/Controllers/StoreController.php` | `final` class — Inertia renders + redirects, delegates to `StoreService` |
| `app/Services/StoreService.php` | `final` class — `create()`, `update()`, `assignUser()` with `DB::transaction()` + activity logging |
| `app/Http/Requests/Store/CreateStoreRequest.php` | Validation + auth via `PermissionsEnum::STORES_CREATE` |
| `app/Http/Requests/Store/UpdateStoreRequest.php` | Validation + auth via `PermissionsEnum::STORES_EDIT`, unique code `Rule::ignore()` |
| `app/Http/Requests/Store/AssignUserToStoreRequest.php` | Validates `user_id` + `role_id`, auth via `PermissionsEnum::STORES_EDIT` |
| `app/Http/Resources/StoreResource.php` | JSON resource — used for both web props and API responses |
| `app/Policies/StorePolicy.php` | All actions check `STORES_*` permissions; `delete()` returns `false` (deactivate only) |

---

## Controller Actions

| Action | Method | Returns |
|---|---|---|
| `index` | GET | `Inertia::render('Stores/Index')` with paginated stores + filters |
| `create` | GET | `Inertia::render('Stores/Create')` |
| `store` | POST | Delegates to `StoreService::create()`, redirects to `stores.show` |
| `show` | GET | `Inertia::render('Stores/Show')` with store + assigned users |
| `edit` | GET | `Inertia::render('Stores/Edit')` with store data |
| `update` | PUT | Delegates to `StoreService::update()`, redirects to `stores.show` |
| `updateStatus` | PATCH | Updates status + logs activity, redirects back |
| `removeLogo` | DELETE | Clears media collection, redirects back |
| `assignUser` | POST | Delegates to `StoreService::assignUser()`, redirects back |
| `updateUserRole` | PATCH | Updates pivot `role_id`, redirects back |
| `removeUser` | DELETE | Detaches user + logs activity, redirects back |

---

## Conventions

### Authorization
Use `$this->authorize(PermissionsEnum::STORES_VIEW, auth()->user())` in controller methods, and `$this->user()->can(PermissionsEnum::STORES_CREATE->value)` in form requests.

### Service Layer
- Wrap create/update in `DB::transaction()` (media upload happens after model save)
- Normalize `code` to uppercase in the service, not in the form
- Use `singleFile()` on media collection — uploading a new logo auto-removes the old one
- Throw `ValidationException` for business rule violations (e.g., duplicate user assignment)

### Resource
- Return `StoreResource` for all responses — never expose raw Eloquent data
- Use `whenCounted('users')` and `whenLoaded('users')` for conditional includes
- Format dates as ISO strings

---

## Routes

```php
// routes/web.php
Route::middleware(['auth', 'can:stores.manage'])->group(function () {
    Route::resource('stores', StoreController::class)->except(['destroy']);
    Route::patch('stores/{store}/status', [StoreController::class, 'updateStatus'])
         ->name('stores.status');
    Route::delete('stores/{store}/logo', [StoreController::class, 'removeLogo'])
         ->name('stores.logo.remove');
    Route::post('stores/{store}/users', [StoreController::class, 'assignUser'])
         ->name('stores.users.assign');
    Route::patch('stores/{store}/users/{user}', [StoreController::class, 'updateUserRole'])
         ->name('stores.users.role');
    Route::delete('stores/{store}/users/{user}', [StoreController::class, 'removeUser'])
         ->name('stores.users.remove');
});
```

---

## Activity Logging

Log all store management actions using `activity('stores')`:

Events: `created`, `updated`, `status_changed`, `user_assigned`, `user_removed`, `logo_removed`
