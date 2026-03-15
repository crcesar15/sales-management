# Task 04 — API: Stock Adjustments

## Endpoints

| Method | Path | Description |
|---|---|---|
| `GET` | `/inventory/adjustments` | Paginated adjustment list |
| `POST` | `/inventory/adjustments` | Create a stock adjustment |
| `GET` | `/inventory/adjustments/{adjustment}` | Single adjustment detail |

> No update or delete — adjustments are immutable records.

## Query Parameters — `GET /inventory/adjustments`
| Param | Type | Description |
|---|---|---|
| `store_id` | int | Filter by store |
| `product_variant_id` | int | Filter by variant |
| `reason` | string | Filter by reason enum |
| `user_id` | int | Filter by who made it (Admin only) |
| `date_from` | date | Created after date |
| `date_to` | date | Created before date |
| `per_page` | int | Default 25 |

## POST `/inventory/adjustments` — Request Body
```json
{
  "product_variant_id": 12,
  "store_id": 1,
  "batch_id": 7,
  "quantity_change": -5,
  "reason": "damage",
  "notes": "Water damage found during inspection"
}
```
> `batch_id` is optional. Omit to auto-select the oldest active batch.

## Response Shape — `GET /inventory/adjustments`
```json
{
  "data": [
    {
      "id": 22,
      "product_variant": { "id": 12, "label": "Size 42 / Black", "product_name": "Running Shoe" },
      "store": { "id": 1, "name": "Main Branch" },
      "user": { "id": 5, "name": "Admin User" },
      "batch_id": 7,
      "quantity_change": -5,
      "reason": "damage",
      "notes": "Water damage found during inspection",
      "created_at": "2026-03-15T10:00:00Z"
    }
  ],
  "meta": { "current_page": 1, "total": 38 }
}
```

## Authorization Notes
- `POST` requires `stock.adjust` permission
- `GET` (list): Admin sees all; Sales Rep sees only their own (`user_id = auth()->id()`)
- `GET` (single): 403 if Sales Rep tries to view another user's adjustment
- `user_id` filter param ignored for Sales Rep (always scoped to self)
