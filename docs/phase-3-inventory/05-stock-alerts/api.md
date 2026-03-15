# Task 05 — API: Stock Alerts

## Endpoints

| Method | Path | Description |
|---|---|---|
| `GET` | `/inventory/alerts` | Full alert list (low stock + expiry) |
| `GET` | `/inventory/alerts/summary` | Counts only — for dashboard widget |

## Query Parameters — `GET /inventory/alerts`
| Param | Type | Description |
|---|---|---|
| `type` | string | `low_stock` or `expiry` — filter by alert type |
| `store_id` | int | Scope to a specific store (Admin only) |

## Response Shape — `GET /inventory/alerts`
```json
{
  "low_stock": [
    {
      "variant_id": 12,
      "product_name": "Running Shoe",
      "variant_label": "Size 42 / Black",
      "minimum_stock_level": 10,
      "current_stock": 3,
      "deficit": 7,
      "affected_stores": [
        { "store_id": 1, "store_name": "Main Branch", "stock": 3 }
      ]
    }
  ],
  "expiry": [
    {
      "batch_id": 7,
      "product_name": "Running Shoe",
      "variant_label": "Size 42 / Black",
      "store": { "id": 1, "name": "Main Branch" },
      "expiry_date": "2026-04-01",
      "days_remaining": 17,
      "remaining_quantity": 22
    }
  ]
}
```

## Response Shape — `GET /inventory/alerts/summary`
```json
{
  "low_stock_count": 4,
  "expiry_count": 2,
  "total": 6
}
```

## Notes
- `summary` endpoint is used by the dashboard widget (lightweight, no pagination)
- `alerts` list is not paginated — alert count should be manageable; large results indicate config issue
- Sales Rep: response automatically scoped to their store (no `store_id` param needed)
- `deficit` = `minimum_stock_level - current_stock` (always positive in results)
- `days_remaining` computed from `expiry_date - today`; negative = already expired
