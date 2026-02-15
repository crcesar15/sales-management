# Task: User Management (CRUD)

## Goal
Ability to Create, Read, Update, and Delete system users.

## Technical Implementation

### 1. Database
- [ ] **Migration**: Check `create_users_table`. Ensure `$table->softDeletes()` is present.

### 2. Backend Logic
- [ ] **Request Validation**: `php artisan make:request StoreUserRequest`
    -   Rules: `name` (required), `email` (required|unique), `password` (required|min:8), `role` (required).
- [ ] **Controller**: `UserController`
    -   `index()`: `User::with('roles')->paginate(10)`. Return via Inertia.
    -   `store(StoreUserRequest $request)`:
        -   Create User.
        -   `$user->assignRole($request->role)`.
    -   `update(UpdateUserRequest $request, User $user)`:
        -   Update fields.
        -   `$user->syncRoles($request->role)`.
    -   `destroy(User $user)`:
        -   Prevent deleting own account.
        -   `$user->delete()`.

### 3. Frontend Implementation
- [ ] **List Page**: `resources/js/Pages/Users/Index.vue`
    -   **Datatable**: Display Name, Email, Role, Created At.
    -   **Actions**: Edit (Icon), Delete (Icon with Confirmation).
- [ ] **Form Pages**: `Create.vue` / `Edit.vue`
    -   Use `MainLayout`.
    -   **Role Selection**: Dropdown populated by `Role::all()`.

### 4. Verification
-   Create a user "John Doe" with role "Cashier".
-   Login as "John Doe" and verify permissions (once Middleware is ready).
