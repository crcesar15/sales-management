# Task 01: Vendor Management — Overview

## What
CRUD management of suppliers/vendors used in the purchasing workflow.

## Why
Vendors are the source of all purchased inventory. Accurate vendor records are required before creating purchase orders or catalog entries.

## Requirements
- Create, read, update, and delete vendors
- Status values: `active`, `inactive`, `archived`
- `additional_contacts` stores a JSON array of secondary contacts `{name, phone, email, role}`
- `meta` is a freeform JSON field for extensibility
- Hard delete only — no soft deletes
- **Deletion guard**: block delete if vendor has existing purchase orders OR catalog entries
- Searchable by name/email; filterable by status; paginated (server-side)
- Admin-only; requires `vendors.manage` permission

## Acceptance Criteria
- [ ] Vendor list page loads with search, status filter, and pagination
- [ ] Create/edit form validates all fields and saves correctly
- [ ] `email` uniqueness is enforced at DB and application level
- [ ] Delete is blocked with a clear error when POs or catalog entries exist
- [ ] `additional_contacts` array is rendered and editable in UI
- [ ] All actions are gated by `vendors.manage` permission

## Dependencies
- `spatie/laravel-permission` — permission gate
- `spatie/laravel-activitylog` — audit trail
- Inertia.js + Vue 3 + PrimeVue — frontend
- Phase 4 Task 02 (Vendor Catalog) consumes this model
- Phase 4 Task 03 (Purchase Orders) consumes this model

## Notes
- No relationship to stores; vendors are global
- `archived` status is a soft "hide" state; vendor is not deleted but excluded from PO creation
- Phone and address are free-text; no strict formatting enforced
