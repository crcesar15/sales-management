# Task 01 — Customer Management

## What
CRUD management for customers used in the POS and sales workflows. Customers are optional on a sale (walk-in supported).

## Why
Enables attaching buyer identity to sales orders for reporting, loyalty, and receipt personalisation. Walk-in mode ensures no friction at checkout.

## Requirements
- Create, edit, delete customers with basic contact and tax info
- Fields: `first_name`, `last_name`, `email` (unique), `phone`, `tax_id`, `tax_id_name`
- No soft deletes — hard delete only
- Deletion guard: block delete if the customer has any associated `sales_orders`
- Searchable from the POS checkout screen (by name, email, phone)
- A sale may be completed with no customer attached (walk-in / anonymous)
- List page: searchable, paginated

## Acceptance Criteria
- [ ] Customers can be created, edited, and deleted via the management UI
- [ ] Deleting a customer with existing sales orders returns a validation error
- [ ] POS customer search returns results by partial name, email, or phone
- [ ] A checkout can be completed with no customer selected
- [ ] List is paginated and filterable by search term
- [ ] All mutations are gated by `customers.manage` permission

## Permissions
| Permission | Scope |
|---|---|
| `customers.manage` | Create / edit / delete customers |
| `sales.create` | View and search customers from POS |

## Dependencies
- `spatie/laravel-permission` — permission gates
- `sales_orders` table — deletion guard FK check

## Notes
- Email uniqueness is nullable-safe (multiple NULLs allowed)
- `tax_id` / `tax_id_name` support business buyers (e.g. VAT number + company name)
- No relationship to `users` table — customers are external contacts
