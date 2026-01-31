# Phase 1: Detailed Developer Tasks

## 1. Database Schema

### Task 1.1: Set up Users Table (Enhancement)
- **Description**: Laravel comes with a default `users` table. We need to verify it and add SoftDeletes.
- **Steps**:
    1.  Check `database/migrations/0001_01_01_000000_create_users_table.php`.
    2.  Ensure fields: `name`, `email`, `password`.
    3.  Add `$table->softDeletes();` to the schema.
- **Acceptance Criteria**: `users` table exists and supports soft deletes.

### Task 1.2: Set up Roles and Permissions Tables
- **Description**: Install and configure `spatie/laravel-permission` package.
- **Steps**:
    1.  Run `composer require spatie/laravel-permission`.
    2.  Publish the migration: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`.
    3.  Run `php artisan migrate`.
- **Acceptance Criteria**: Tables `roles`, `permissions`, `model_has_roles`, etc., exist in the database.

### Task 1.3: Set up Settings Table
- **Description**: Create a table to store system-wide key-value settings.
- **Steps**:
    1.  Run `php artisan make:migration create_settings_table`.
    2.  Schema:
        ```php
        $table->string('key')->primary();
        $table->text('value')->nullable();
        $table->string('type')->default('string'); // string, boolean, int, json
        $table->string('description')->nullable();
        $table->timestamps();
        ```
    3.  Run `php artisan migrate`.
- **Acceptance Criteria**: `settings` table created.

## 2. Backend Implementation (Laravel)

### Task 2.1: Configure Authentication (Sanctum/Fortify)
- **Description**: Ensure the authentication system works for both API (Sanctum) and Web (Session/Inertia).
- **Steps**:
    1.  Verify `User` model uses `HasApiTokens`.
    2.  Verify `config/sanctum.php`.
    3.  Ensure `EnsureFrontendRequestsAreStateful` middleware is in `app/Http/Kernel.php` (or `bootstrap/app.php` in Laravel 11/12).
- **Acceptance Criteria**: Login endpoint returns a valid session or token.

### Task 2.2: Implement Role Based Access Control (RBAC)
- **Description**: Integrate Spatie Permission traits into User model.
- **Steps**:
    1.  Open `app/Models/User.php`.
    2.  Add `use Spatie\Permission\Traits\HasRoles;`.
    3.  Add `use HasRoles` inside the class.
- **Acceptance Criteria**: Can call `$user->assignRole('admin')` in Tinker.

### Task 2.3: Create Controllers and Routes
- **Description**: scaffolding for User and Role management.
- **Steps**:
    1.  `php artisan make:controller UserController`.
    2.  `php artisan make:controller RoleController`.
    3.  `php artisan make:controller SettingController`.
    4.  Define routes in `routes/web.php` (wrapped in `auth` middleware).
- **Acceptance Criteria**: Routes exist and map to controller methods.

### Task 2.4: Seed Default Data
- **Description**: We need a Super Admin to log in initially.
- **Steps**:
    1.  Create `database/seeders/RolesAndPermissionsSeeder.php`. Create roles: 'Admin', 'Manager', 'Cashier'.
    2.  Create `database/seeders/AdminUserSeeder.php`. Create a user with email `admin@example.com` and assign 'Admin' role.
    3.  Register seeders in `DatabaseSeeder.php`.
- **Acceptance Criteria**: Running `php artisan db:seed` populates the DB with the admin user.

## 3. Frontend Implementation (Vue 3 + PrimeVue)

### Task 3.1: Setup Layouts
- **Description**: Create the main application shell.
- **Steps**:
    1.  Create `resources/js/Layouts/MainLayout.vue`.
    2.  Implement a specific Sidebar with links (Dashboard, Users, Roles, Settings).
    3.  Implement a Topbar with User Profile Dropdown (Logout).
    4.  Ensure `GuestLayout.vue` exists for Login.
- **Acceptance Criteria**: Authenticated user sees the Sidebar/Layout.

### Task 3.2: User Management Pages
- **Description**: CRUD for Users.
- **Steps**:
    1.  `resources/js/Pages/Users/Index.vue`: Use PrimeVue `DataTable` to list users.
    2.  `resources/js/Pages/Users/Create.vue`: Form with Name, Email, Password, Role (MultiSelect).
    3.  `resources/js/Pages/Users/Edit.vue`: Update details.
- **Acceptance Criteria**: Admin can create a new user and see it in the list.

### Task 3.3: Role Management Pages
- **Description**: CRUD for Roles and Permissions.
- **Steps**:
    1.  `resources/js/Pages/Roles/Index.vue`: List roles.
    2.  `resources/js/Pages/Roles/Edit.vue`: Checkboxes for available permissions.
- **Acceptance Criteria**: Admin can assign/revoke permissions for a role.
