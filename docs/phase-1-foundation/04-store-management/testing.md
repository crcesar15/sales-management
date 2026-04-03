# Store Management — Testing

## Test File Locations
```
tests/Feature/Stores/StoreManagementTest.php
tests/Feature/Stores/StoreUserAssignmentTest.php
```

---

## Test Plan

### `StoreManagementTest`

**Access Control**
- Admin can list stores → `assertStatus(200)`
- Admin can view create page → `assertStatus(200)`
- Admin can view edit page → `assertStatus(200)`
- Salesman denied listing → `getJson()` + `assertForbidden()`
- Salesman denied create page → `getJson()` + `assertForbidden()`
- Salesman denied creating → `postJson()` + `assertForbidden()`
- Salesman denied updating → `putJson()` + `assertForbidden()`
- Salesman denied deleting → `deleteJson()` + `assertForbidden()`
- Salesman denied restoring → `putJson()` + `assertForbidden()`

> Note: `getJson()`/`postJson()` used for forbidden assertions to avoid `storage/logs/laravel.log` permission issues.

**Create Store**
- Creates with valid data → `assertRedirect(route('stores'))` + `assertDatabaseHas()`
- Code is uppercased on creation → verify DB has uppercase code
- Validates required fields → `post()` with `['Accept' => 'application/json']` + `assertStatus(422)` + `assertJsonValidationErrors(['name', 'code', 'status'])`
- Validates email format → 422 + `assertJsonValidationErrors(['email'])`
- Validates field max lengths (zip_code: 20, phone: 30) → 422 + `assertJsonValidationErrors(['zip_code', 'phone'])`
- Enforces unique store code → 422 + `assertJsonValidationErrors(['code'])`

**Update Store**
- Updates store successfully → `assertRedirect(route('stores'))` + `assertDatabaseHas()`
- Allows update with same code (no unique violation) → `Rule::ignore()` in `UpdateStoreRequest`
- Validates required fields on update → 422 + `assertJsonValidationErrors()`

**Status Toggle**
- Changes status to inactive → `patch()` + `assertRedirect()` + verify in DB

**Soft Delete**
- Soft-deletes a store → `assertRedirect(route('stores'))` + `assertSoftDeleted()`
- Soft-deleted store does not appear in active listing → verify `Store::query()->count()` excludes deleted
- Store code uniqueness includes soft-deleted stores → 422 when reusing code of deleted store

**Restore**
- Restores a soft-deleted store → `assertRedirect(route('stores'))` + `assertDatabaseHas()` with `deleted_at` null
- Restored store appears in active listing → `Store::query()->where('id', ...)->exists()` is true
- Restored store status resets to active → verify DB has `status` = 'active'

**Activity Logging**
- Creating a store logs `created` event → verify `activity_log` entry with `log_name` = 'store', `subject_id`, `causer_id`
- Updating a store logs `updated` event with dirty attributes
- Deleting a store logs `deleted` event
- Restoring a store logs `restored` event

---

### `StoreUserAssignmentTest`

User assignment is handled through the store update route (`PUT /stores/{store}`) with a `users` array field. The service uses `sync()` to manage the relationship.

**Assign**
- Assigns users via update with `users` array → verify `store_user` pivot record
- Syncs users (replaces previous assignments) → new user present, old user removed from pivot
- Removes all users when empty array passed → verify pivot record deleted
- Validates users must exist in database → 422 + `assertJsonValidationErrors(['users.0'])`

**Activity Logging**
- Logs activity when users are assigned → verify `activity_log` entry with `description` = 'updated', `log_name` = 'store', `causer_id`

> Note: When only the users relationship changes (no store model data changes), the activity log is created by the manual `activity()` helper in the service, which sets `description` (not `event`). The `LogsActivity` trait only fires when model attributes actually change.

---

## Key Implementation Notes

- Store module has **web routes only** (no API routes) — all tests use web route names (`stores`, `stores.store`, `stores.update`, etc.)
- Mutations (create, update, delete, restore, status) return redirects, not JSON
- For validation tests, use `['Accept' => 'application/json']` header to receive 422 JSON responses instead of redirects with session errors
- For forbidden tests, use `getJson()`/`postJson()` to avoid log file permission issues
- Store factory generates: company name, unique code (`???##` format), optional address fields, random status

---

## Coverage Goals
- [x] All CRUD operations tested (create, read, update, soft delete, restore)
- [x] Permission enforcement (admin allowed, salesman denied)
- [x] Validation rules (required fields, unique code, email format, field max lengths)
- [x] Store code is uppercased
- [x] Update with same code does not trigger unique constraint violation
- [x] Soft delete excludes store from active listings
- [x] Restore brings store back and resets status to active
- [x] User assignment via update, sync behavior, removal
- [x] Activity logging for all key actions (CRUD, status change, user assignments)
