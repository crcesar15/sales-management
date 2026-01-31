# Phase 1: Core Foundation & Administration

**Goal**: Secure implementation of the base system, authentication, and user management.

## 1. Database Schema
- **users**: `id`, `name`, `email`, `password`, `created_at`, `updated_at`, `deleted_at` (SoftDeletes).
- **roles**: (Spatie Permission) `id`, `name`, `guard_name`.
- **permissions**: (Spatie Permission) `id`, `name`, `guard_name`.
- **model_has_roles**, **model_has_permissions**, **role_has_permissions**: Pivot tables.
- **settings**: `key`, `value` (JSON or Text), `type` (string, boolean, integer), `description`.

## 2. Backend Implementation (Laravel)
### Authentication & Security
- Install/Configure `laravel/sanctum` for API authentication (or use default session auth with Inertia).
- verify `config/cors.php` and `config/sanctum.php`.
- Setup `Spatie\Permission\Traits\HasRoles` on `User` model.

### API & Controllers
- **AuthController**: Login, Logout, User Profile.
- **UserController**: CRUD for users. Assign roles.
- **RoleController**: CRUD for roles. Assign permissions.
- **SettingController**: Read/Update global settings (e.g., App Name, Currency Symbol).

### Seeds
- `RolesAndPermissionsSeeder`: Create default roles (Admin, Manager, Cashier) and Permissions.
- `AdminUserSeeder`: Create the initial Super Admin user.

## 3. Frontend Implementation (Vue 3 + PrimeVue)
### Layout & Structure
- **MainLayout**: Sidebar navigation, Header (User dropdown, Dark mode toggle).
- **GuestLayout**: For Login/Forgot Password pages.
- **Theme Config**: Setup PrimeVue theme (Tailwind base).

### Pages/Components
- **Login Page**: Email/Password form.
- **Dashboard Placeholder**: Empty state home page.
- **Users**:
    - `Index`: DataTable with pagination/search.
    - `Create/Edit`: Dialog or Page with form (Name, Email, Role selection).
- **Roles**:
    - `Index`: List of roles.
    - `Manage Permissions`: Interface to toggle permissions for a role.
- **Settings**: Simple form to update app configuration (Logo, Currency).

## 4. Deliverables
- [ ] Working Login/Logout.
- [ ] Admin can create other Users.
- [ ] Admin can assign Roles.
- [ ] Application Layout is responsive.
