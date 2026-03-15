# Task 02 — API: Batch Tracking

## Endpoints

| Method | Path | Description |
|---|---|---|
| `GET` | `/inventory/batches` | Paginated batch list |
| `GET` | `/inventory/batches/{batch}` | Single batch details |
| `PATCH` | `/inventory/batches/{batch}/close` | Manually close a batch |

## Query Parameters — `GET /inventory/batches`
| Param | Type | Description |
|---|---|---|
| `status` | string | `queued`, `active`, `closed` |
| `store_id` | int | Filter by store |
| `product_variant_id` | int | Filter by variant |
| `expiry_from` | date | Expiry date range start |
| `expiry_to` | date | Expiry date range end |
| `expiring_soon` | bool | Within `expiry_alert_days` threshold |
| `per_page` | int | Default 25 |

## Response Shape — `GET /inventory/batches`
```json
{
  "data": [
    {
      "id": 7,
      "status": "active",
      "product_variant": { "id": 12, "label": "Size 42 / Black", "product_name": "Running Shoe" },
      "store": { "id": 1, "name": "Main Branch" },
      "reception_order_id": 3,
      "expiry_date": "2026-06-01",
      "expiry_status": "expiring_soon",
      "initial_quantity": 100,
      "remaining_quantity": 34,
      "sold_quantity": 60,
      "transferred_quantity": 6,
      "missing_quantity": 0
    }
  ],
  "meta": { "current_page": 1, "total": 58 }
}
```

## Response Shape — `GET /inventory/batches/{batch}`
Same as list item above, plus:
```json
{
  "reception_order": { "id": 3, "reference": "RO-2026-003", "received_at": "2026-01-15" }
}
```

## PATCH `/inventory/batches/{batch}/close`
- Request body: `{ "notes": "Manually closed after audit" }` (optional)
- Response: `204 No Content` on success
- Returns `403` if user lacks `stock.adjust` permission
- Returns `422` if batch is already closed

## Notes
- `expiry_status` values: `"ok"`, `"expiring_soon"`, `"expired"`, `null` (no expiry date)
- All endpoints require `auth` middleware; close requires `stock.adjust` permission
