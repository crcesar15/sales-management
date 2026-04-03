# Roles & Permissions — Overview

## What
A role-based access control (RBAC) system built on top of Spatie Laravel Permission. Two fixed roles are defined: `admin` and `sales_rep`. Permissions are categorized and enforced on both the backend (Policies, middleware) and frontend (Vue computed properties, directives).

## Why
The system must allow fine-grained control over what each user can do. Admins have full access; Sales Reps start with minimal access, and their additional permissions are configurable per installation. This prevents unauthorized access to sensitive operations like approving purchase orders, adjusting stock, or viewing all reports.

## Requirements
- Two fixed roles: `admin` and `sales_rep` (no UI to create new roles)
- A defined set of 16 named permissions organized by category
- Admin role receives all permissions automatically (via seeder + `syncPermissions`)
- Sales Rep role receives `sales.create` and `reports.view_own` by default; all other permissions are configurable
- UI to assign/revoke individual permissions to/from the `sales_rep` role
- Permissions are stored with a `category` column for grouped display in the UI
- Users are assigned to stores via `store_user` pivot for access control; permissions are determined by the user's global role via `spatie/laravel-permission`
- Backend enforcement via Laravel Policies and `can:` middleware
- Frontend enforcement via computed properties and a custom `v-can` directive

## Permission List

| Permission | Category | Description |
|---|---|---|
| `users.manage` | Administration | Create, edit, delete users |
| `stores.manage` | Administration | Create, edit, configure stores |
| `products.manage` | Inventory | Create, edit, delete products |
| `vendors.manage` | Inventory | Create, edit, delete vendors |
| `purchase_orders.create` | Purchasing | Create new purchase orders |
| `purchase_orders.approve` | Purchasing | Approve or reject purchase orders |
| `reception_orders.manage` | Purchasing | Receive and reconcile purchase orders |
| `stock.adjust` | Inventory | Manual stock adjustments |
| `sales.create` | Sales | Create new sales transactions (POS) |
| `sales.manage` | Sales | Edit, cancel, void sales |
| `sales.view_all` | Sales | View sales from all users/stores |
| `customers.manage` | Sales | Create and manage customer records |
| `refunds.manage` | Sales | Process refunds and returns |
| `reports.view_own` | Reports | View own sales reports |
| `reports.view_all` | Reports | View all reports across stores |
| `settings.manage` | Administration | Manage system settings |

## Acceptance Criteria
- [ ] Seeder creates `admin` and `sales_rep` roles
- [ ] Seeder creates all 16 permissions with correct categories
- [ ] Admin role is synced with all 16 permissions
- [ ] Sales Rep role has `sales.create` and `reports.view_own` by default
- [ ] Admin can view the permissions configuration UI
- [ ] Admin can toggle individual permissions for the `sales_rep` role
- [ ] Permission changes take effect immediately (no cache stale state)
- [ ] Backend routes are protected by the correct `can:` middleware or Policy checks
- [ ] Frontend hides/disables UI elements based on user permissions
- [ ] `v-can` directive is globally registered and works on any element
- [ ] Permissions are shared as Inertia shared props on every page load

## Dependencies
- Phase 1: Authentication (user must be authenticated)
- Phase 1: User Management (users are assigned to roles)
- Phase 1: Store Management (users are assigned to stores via `store_user` for access)
- `spatie/laravel-permission` package installed and migrated

## Notes
- Role creation is intentionally locked down — no UI for adding new roles
- The `category` column on the `permissions` table is a custom addition beyond the Spatie default schema
- Permissions are cached by Spatie by default; cache must be reset after any permission sync
- Store assignment is independent of roles — a user's permissions come from their global role via `spatie/laravel-permission`, not from any store-level role
- For v1 (single store), every user has one role system-wide
