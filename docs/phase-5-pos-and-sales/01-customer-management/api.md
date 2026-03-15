# API — Customer Management

## Endpoints

| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/customers` | Paginated list with search | `customers.manage` |
| `POST` | `/customers` | Create customer | `customers.manage` |
| `PUT` | `/customers/{customer}` | Update customer | `customers.manage` |
| `DELETE` | `/customers/{customer}` | Delete customer (guarded) | `customers.manage` |
| `GET` | `/customers/search` | POS typeahead search | `sales.create` |

## Route File
All routes registered in `routes/web.php` under the `auth` middleware group via Inertia (not a JSON API).

## POS Search Endpoint
```
GET /customers/search?q=john
```
Returns lightweight JSON (used by POS Vue component via `axios`):
```json
[
  { "id": 1, "display_name": "John Smith", "email": "john@example.com", "phone": "081234567" }
]
```
- Max 10 results
- Searches: `first_name`, `last_name`, `email`, `phone` with `LIKE %q%`
- Returns `display_name` = computed `first_name + last_name` (or email/phone fallback)

## Inertia Page Props (List)
```json
{
  "customers": {
    "data": [...],
    "links": {...},
    "meta": { "total": 42, "per_page": 20 }
  },
  "filters": { "search": "john" }
}
```

## Validation Rules (Create / Update)
| Field | Rules |
|---|---|
| `first_name` | `nullable\|string\|max:100` |
| `last_name` | `nullable\|string\|max:100` |
| `email` | `nullable\|email\|unique:customers,email,{id}` |
| `phone` | `nullable\|string\|max:30` |
| `tax_id` | `nullable\|string\|max:50` |
| `tax_id_name` | `nullable\|string\|max:150` |
