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
- Admin can list stores → `assertOk()` + `assertInertia(component, has data)`
- Salesman denied access → `assertForbidden()`

**Create Store**
- Creates with valid data → `assertRedirect()` + `assertDatabaseHas()`
- Code is uppercased on creation
- Validates required fields → `assertSessionHasErrors(['name', 'code', 'status'])`
- Validates optional fields: `email` must be valid email format, `zip_code` max 20 chars, `phone` max 30 chars
- Enforces unique store code

**Update Store**
- Updates store successfully → `assertRedirect()`
- Allows update with same code (no unique violation) → use `Rule::ignore()`
- Updates address fields, phone, email

**Status Toggle**
- Changes status to inactive → `assertRedirect()` + verify in DB

**Soft Delete**
- Soft-deletes a store → `assertSoftDeleted()` in DB
- Soft-deleted store does not appear in index listing
- Store code remains unique constraint respected (including soft-deleted stores)

**Restore**
- Restores a soft-deleted store → `assertDatabaseHas()` with `deleted_at` null
- Restored store appears in index listing again

**Activity Logging**
- Creating a store logs `created` event → verify `activity_log` entry with correct `log_name`, `subject_id`, `causer_id`
- Updating a store logs `updated` event with only dirty attributes
- Soft-deleting a store logs `deleted` event
- Restoring a store logs `restored` event

---

### `StoreUserAssignmentTest`

**Assign**
- Assigns user to store with role → verify `store_user` pivot record
- Prevents duplicate assignment → `assertSessionHasErrors()`

**Remove**
- Removes user from store → verify pivot record deleted

**Update Role**
- Updates user role within store → verify pivot `role_id` changed

**Activity Logging**
- Logs `user_assigned` event → verify `activity_log` entry with correct `log_name`, `subject_id`, `causer_id`
- Logs `user_removed` event

---

## Coverage Goals
- All CRUD operations tested (create, read, update, soft delete, restore)
- Permission enforcement (admin allowed, salesman denied)
- Validation rules (required fields, unique code, email format, field max lengths)
- Store code is uppercased
- Update with same code does not trigger unique constraint violation
- Soft delete excludes store from listings
- Restore brings store back to listings
- User assignment, duplicate prevention, role update, removal
- Activity logging for all key actions (CRUD, status change, user assignments)
