# User Management — Overview

## What
CRUD operations for system users, including profile management, status control, and store assignments.

## Why
The system needs a way for admins to manage who has access, what stores they belong to, and whether they are active. User management is the foundation for all role-based access control.

## Requirements
- Create users with: first name, last name, email, username, phone, date of birth, status
- Edit user profile information
- Change user status (active, inactive, archived)
- Soft-delete users (archived users cannot log in but data is retained)
- Assign users to one or more stores
- Search and filter users by name, email, status, and store
- Paginated user list
- Admin-only access

## Acceptance Criteria
- [ ] Admin can create a new user with required fields validated
- [ ] Admin can edit any user's profile information
- [ ] Admin can change a user's status to active, inactive, or archived
- [ ] Inactive and archived users cannot log in
- [ ] Admin can soft-delete a user (sets `deleted_at`, does not remove from DB)
- [ ] Admin can assign a user to one or more stores with a role
- [ ] Admin can remove a user from a store
- [ ] User list is paginated (20 per page)
- [ ] User list is searchable by name and email
- [ ] User list is filterable by status and store
- [ ] Admin cannot delete their own account
- [ ] Email and username must be unique across all users

## Dependencies
- Phase 1: Roles & Permissions (users need roles assigned)
- Phase 1: Store Management (users are assigned to stores)

## Notes
- Passwords are auto-generated on creation and sent via email, OR admin sets a temporary password
- User avatars are a nice-to-have (out of scope for v1)
- The `additional_properties` JSON column can store flexible metadata for future use
