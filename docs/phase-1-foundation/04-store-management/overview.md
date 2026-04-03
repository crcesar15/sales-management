# Store Management — Overview

## What
CRUD operations for physical store locations. Each store has a name, unique code, structured address (street, city, state, zip code), contact info (phone, email), and status. Stores support soft deletes for safe removal with the ability to restore. Users are assigned to stores with a specific role via the `store_user` pivot table. All store actions are logged via Spatie Activity Log for a full audit trail.

## Why
Stores are the top-level organizational unit in the system. All sales, stock, purchase orders, and reports are scoped to a store. While v1 launches with a single store, the architecture supports multiple stores from day one. Admins must be able to configure stores and assign staff to them.

## Requirements
- Create a store with: name, code (unique), address (street, city, state, zip code), phone, email, status (active/inactive)
- Edit store details
- Change store status (active/inactive)
- Soft delete a store (sets `deleted_at`, does not permanently remove)
- Restore a soft-deleted store
- List all stores with pagination and search
- Assign a user to a store with a role (`admin` or `sales_rep`)
- Remove a user from a store
- View users assigned to a store
- Admin-only access (requires `STORE_VIEW`, `STORE_CREATE`, `STORE_EDIT`, `STORE_DELETE`, `STORE_RESTORE` permissions via `PermissionsEnum`)
- Log all store management actions using `LogsActivity` trait for audit trail

## Acceptance Criteria
- [ ] Admin can create a new store with all required fields validated
- [ ] Store `code` is unique across all stores (including soft-deleted)
- [ ] Admin can edit store name, code, address, phone, email, and status
- [ ] Admin can deactivate a store (status → inactive)
- [ ] Admin can soft-delete a store
- [ ] Admin can restore a soft-deleted store
- [ ] Admin can list stores with pagination (20 per page)
- [ ] Admin can search stores by name or code
- [ ] Admin can assign a user to a store with a role
- [ ] Admin can remove a user from a store
- [ ] Store detail page shows assigned users and their roles
- [ ] Inactive stores are clearly indicated in the UI
- [ ] Soft-deleted stores are excluded from listings by default
- [ ] All store CRUD actions are logged in the activity log

## Dependencies
- Phase 1: Authentication (admin must be logged in)
- Phase 1: Roles & Permissions (`STORE_*` permissions in `PermissionsEnum`, role assignments)
- Phase 1: User Management (users are assigned to stores)
- `spatie/laravel-activitylog` installed and configured

## Notes
- At launch: 1 store. The UI and architecture support multiple stores
- The store `code` is used as a short identifier in reports and receipts (e.g., "HQ", "BRANCH1")
- Address is structured as separate fields (address, city, state, zip_code) for filtering and reporting by location
- Phone and email are for store contact information, useful for receipts and communications
- Soft deletes are used instead of permanent deletion to preserve data integrity and referential history
- `store_user` pivot is defined here but first referenced in User Management docs
