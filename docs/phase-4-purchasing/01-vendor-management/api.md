# Task 01: Vendor Management — API

## Endpoints

| Method | Path                    | Description                          | Permission      |
|--------|-------------------------|--------------------------------------|-----------------|
| GET    | `/vendors`              | Paginated list (search + filter)     | vendors.manage  |
| POST   | `/vendors`              | Create vendor                        | vendors.manage  |
| GET    | `/vendors/{vendor}`     | Show single vendor                   | vendors.manage  |
| PUT    | `/vendors/{vendor}`     | Update vendor                        | vendors.manage  |
| DELETE | `/vendors/{vendor}`     | Hard delete (guarded)                | vendors.manage  |

## Query Parameters — GET `/vendors`

| Param    | Type   | Description                              |
|----------|--------|------------------------------------------|
| search   | string | Matches `fullname` or `email` (LIKE)     |
| status   | string | Filter by `active`, `inactive`, `archived` |
| per_page | int    | Default 15                               |

## Request Body — POST/PUT `/vendors`

```json
{
  "fullname": "Acme Supplies",
  "email": "orders@acme.com",
  "phone": "555-0100",
  "address": "123 Warehouse Rd",
  "details": "Preferred supplier for dry goods",
  "status": "active",
  "additional_contacts": [
    { "name": "Jane Doe", "phone": "555-0101", "email": "jane@acme.com", "role": "Billing" }
  ],
  "meta": {}
}
```

## Response — Vendor Resource

```json
{
  "id": 1,
  "fullname": "Acme Supplies",
  "email": "orders@acme.com",
  "phone": "555-0100",
  "status": "active",
  "additional_contacts": [...],
  "created_at": "2026-01-15T10:00:00Z"
}
```

## Error Responses

| Status | Scenario                                          |
|--------|---------------------------------------------------|
| 422    | Validation failure (email uniqueness, required)   |
| 409    | Delete blocked — vendor has POs or catalog entries |
| 403    | Missing `vendors.manage` permission               |
