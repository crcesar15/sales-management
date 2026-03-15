# Task 01: Vendor Management — Database

## Table: `vendors`

| Column               | Type              | Notes                              |
|----------------------|-------------------|------------------------------------|
| id                   | bigIncrements     | PK                                 |
| fullname             | string            | required                           |
| email                | string, nullable  | unique when present                |
| phone                | string, nullable  |                                    |
| address              | string, nullable  |                                    |
| details              | text, nullable    | general notes                      |
| status               | enum              | `active`, `inactive`, `archived`   |
| additional_contacts  | json, nullable    | array of contact objects           |
| meta                 | json, nullable    | freeform extensibility             |
| created_at           | timestamp         |                                    |
| updated_at           | timestamp         |                                    |

### `additional_contacts` JSON Shape
```json
[
  { "name": "Jane Doe", "phone": "555-0101", "email": "jane@vendor.com", "role": "Billing" }
]
```

## New Migration
- `create_vendors_table` — creates the table above
- Unique index on `email` (sparse: only unique when not null — handled at app level)

## Key Indexes
```php
$table->index('status');
$table->index('fullname');
$table->unique('email'); // DB enforces; nullable handled in app
```

## Relationships

| From       | To                    | Type       | Notes                          |
|------------|-----------------------|------------|--------------------------------|
| vendors    | purchase_orders       | hasMany    | blocks delete if exists        |
| vendors    | catalog               | hasMany    | blocks delete if exists        |

## Notable Patterns
- **No soft deletes** — hard delete with a pre-delete guard
- Guard checks `purchase_orders` and `catalog` counts before deletion
- `status = archived` is the preferred "hide" path instead of deletion
- `meta` cast to `array` on the Eloquent model
- `additional_contacts` cast to `array` on the Eloquent model
