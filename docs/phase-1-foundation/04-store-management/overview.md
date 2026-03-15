# Store Management — Overview

## What
CRUD operations for physical store locations. Each store has a name, unique code, address, and status. Stores can have a logo uploaded via Spatie Media Library. Users are assigned to stores with a specific role via the `store_user` pivot table.

## Why
Stores are the top-level organizational unit in the system. All sales, stock, purchase orders, and reports are scoped to a store. While v1 launches with a single store, the architecture supports multiple stores from day one. Admins must be able to configure stores and assign staff to them.

## Requirements
- Create a store with: name, code (unique), address, status (active/inactive)
- Upload/replace a store logo (Spatie Media Library, collection: `logo`)
- Remove a store logo
- Edit store details
- Change store status (active/inactive)
- List all stores with pagination and search
- Assign a user to a store with a role (`admin` or `sales_rep`)
- Remove a user from a store
- View users assigned to a store
- Admin-only access (requires `stores.manage` permission)

## Acceptance Criteria
- [ ] Admin can create a new store with all required fields validated
- [ ] Store `code` is unique across all stores
- [ ] Admin can upload a store logo (image file, max 2MB)
- [ ] Admin can remove a store logo
- [ ] Admin can edit store name, code, address, and status
- [ ] Admin can deactivate a store (status → inactive)
- [ ] Admin can list stores with pagination (20 per page)
- [ ] Admin can search stores by name or code
- [ ] Admin can assign a user to a store with a role
- [ ] Admin can remove a user from a store
- [ ] Store detail page shows assigned users and their roles
- [ ] Inactive stores are clearly indicated in the UI

## Dependencies
- Phase 1: Authentication (admin must be logged in)
- Phase 1: Roles & Permissions (`stores.manage` permission, role assignments)
- Phase 1: User Management (users are assigned to stores)
- `spatie/laravel-medialibrary` installed and configured

## Notes
- At launch: 1 store. The UI and architecture support multiple stores
- The store `code` is used as a short identifier in reports and receipts (e.g., "HQ", "BRANCH1")
- Store logo is displayed on receipts if the `receipt.show_logo` setting is enabled
- Deleting a store is intentionally out of scope for v1 — stores can be deactivated instead
- `store_user` pivot is defined here but first referenced in User Management docs
