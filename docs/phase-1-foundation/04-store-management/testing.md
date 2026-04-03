# Store Management — Testing

## Test File Locations
```
tests/Feature/Stores/StoreManagementTest.php
tests/Feature/Stores/StoreLogoTest.php
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
- Enforces unique store code
- Validates logo must be an image file
- Validates logo max 2MB

**Update Store**
- Updates store successfully → `assertRedirect()`
- Allows update with same code (no unique violation) → use `Rule::ignore()`

**Status Toggle**
- Changes status to inactive → `assertRedirect()` + verify in DB

---

### `StoreLogoTest`

**Upload**
- Uploads logo on create → verify media exists via `getFirstMedia('logo')`
- Replaces logo on update (method spoofing `_method: PUT`) → verify old media gone, new exists

**Remove**
- Removes logo → `DELETE` endpoint, verify media cleared

**Setup**: `Storage::fake('public')` in `beforeEach()`

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

---

## Coverage Goals
- All CRUD operations tested
- Permission enforcement (admin allowed, salesman denied)
- Validation rules (required fields, unique code, logo type and size)
- Store code is uppercased
- Update with same code does not trigger unique constraint violation
- Logo upload, replacement, and removal
- User assignment, duplicate prevention, role update, removal
- Activity logging for key actions
