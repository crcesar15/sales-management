# Task: Role Management Module

## Goal
Allow Admins to create custom roles and assign permissions.

## Technical Implementation

### 1. Database & Seeding
- [ ] **Install**: `composer require spatie/laravel-permission`.
- [ ] **Seeder**: `RolesAndPermissionsSeeder`.
    -   Define Permissions: `user.view`, `user.create`, `user.edit`, `user.delete`, `role.manage`, `settings.manage`.
    -   Define Roles: `Admin` (all), `Manager` (some), `Cashier` (sales only).

### 2. Backend Logic
- [ ] **Controller**: `RoleController`
    -   `index()`: List roles with their permission count.
    -   `create()`: Return all available `Permission::all()` to the view.
    -   `store(Request $request)`:
        -   `$role = Role::create(['name' => $request->name])`.
        -   `$role->syncPermissions($request->permissions)`. // Array of permission IDs or Names.

### 3. Frontend Implementation
- [ ] **Form Component**:
    -   **Role Name**: Text Input.
    -   **Permissions**: Grouped Checkboxes (e.g., grouped by Module: "Users", "Settings").
    -   Select All / Deselect All features for UX.

### 4. Security
-   Add `Middleware` or format `Policy` to ensure only `Admin` users can access `RoleController`.
