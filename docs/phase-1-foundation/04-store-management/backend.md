# Store Management — Backend

## File Map

| File | Responsibility |
|---|---|
| `app/Models/Store.php` | Eloquent model with `SoftDeletes`, `LogsActivity`, `BelongsToMany` to users |
| `app/Http/Controllers/StoreController.php` | `final` class — Inertia renders + redirects, delegates to `StoreService` |
| `app/Services/StoreService.php` | `final` class — `create()`, `update()`, `delete()`, `restore()`, `assignUser()` with `DB::transaction()` + activity logging |
| `app/Http/Requests/Store/CreateStoreRequest.php` | Validation + auth via `PermissionsEnum::STORE_CREATE` |
| `app/Http/Requests/Store/UpdateStoreRequest.php` | Validation + auth via `PermissionsEnum::STORE_EDIT`, unique code `Rule::ignore()` |
| `app/Http/Requests/Store/AssignUserToStoreRequest.php` | Validates `user_id`, auth via `PermissionsEnum::STORE_EDIT` |
| `app/Http/Resources/StoreResource.php` | JSON resource — used for both web props and API responses |
| `app/Policies/StorePolicy.php` | All actions check `STORE_*` permissions |

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
| `destroy` | DELETE | Delegates to `StoreService::delete()`, soft deletes store, redirects to `stores.index` |
| `restore` | PATCH | Delegates to `StoreService::restore()`, restores soft-deleted store, redirects to `stores.show` |
| `updateStatus` | PATCH | Updates status + logs activity, redirects back |
| `assignUser` | POST | Delegates to `StoreService::assignUser()`, redirects back |
| `removeUser` | DELETE | Detaches user + logs activity, redirects back |

---

## Conventions

### Authorization
Use `$this->authorize(PermissionsEnum::STORE_VIEW, auth()->user())` in controller methods, and `$this->user()->can(PermissionsEnum::STORE_CREATE->value)` in form requests.

### Model
- Use `SoftDeletes` trait with `deleted_at` column
- Use `LogsActivity` trait with `getActivitylogOptions()` configured: `logFillable`, `logOnlyDirty`, `useLogName('store')`
- Normalize `code` to uppercase via a Laravel Attribute mutator

### Service Layer
- Wrap create/update/delete/restore in `DB::transaction()`
- Normalize `code` to uppercase in the service, not in the form
- Throw `ValidationException` for business rule violations (e.g., duplicate user assignment)
- Soft delete: call `$store->delete()` (sets `deleted_at`)
- Restore: call `$store->restore()` (clears `deleted_at`)

### Resource
- Return `StoreResource` for all responses — never expose raw Eloquent data
- Use `whenCounted('users')` and `whenLoaded('users')` for conditional includes
- Format dates as ISO strings
- Include `deleted_at` when store is trashed

---

## Routes

```php
// routes/web.php
Route::middleware(['auth', 'can:stores.manage'])->group(function () {
    Route::resource('stores', StoreController::class);
    Route::patch('stores/{store}/restore', [StoreController::class, 'restore'])
         ->name('stores.restore');
    Route::patch('stores/{store}/status', [StoreController::class, 'updateStatus'])
         ->name('stores.status');
    Route::post('stores/{store}/users', [StoreController::class, 'assignUser'])
         ->name('stores.users.assign');
    Route::delete('stores/{store}/users/{user}', [StoreController::class, 'removeUser'])
         ->name('stores.users.remove');
});
```

---

## Activity Logging

Use `LogsActivity` trait on the Store model with `getActivitylogOptions()`:

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logFillable()
        ->logOnlyDirty()
        ->useLogName('store');
}
```

Custom activity log events for user assignments (manual logging in service):
- `user_assigned` — when a user is assigned to a store
- `user_removed` — when a user is removed from a store

Automatic events from `LogsActivity`:
- `created` — store created
- `updated` — store updated (including status changes)
- `deleted` — store soft-deleted
- `restored` — store restored
