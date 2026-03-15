# Task 03 — API: Stock Transfers

## Endpoints

| Method | Path | Description |
|---|---|---|
| `GET` | `/inventory/transfers` | Paginated transfer list |
| `POST` | `/inventory/transfers` | Create new transfer request |
| `GET` | `/inventory/transfers/{transfer}` | Transfer details + items |
| `PATCH` | `/inventory/transfers/{transfer}/status` | Advance to next status |
| `DELETE` | `/inventory/transfers/{transfer}` | Cancel transfer |

## Query Parameters — `GET /inventory/transfers`
| Param | Type | Description |
|---|---|---|
| `store_id` | int | Either from or to |
| `status` | string | Filter by workflow status |
| `variant_id` | int | Transfers involving a variant |

## POST `/inventory/transfers` — Request Body
```json
{
  "from_store_id": 1,
  "to_store_id": 2,
  "notes": "Optional reason",
  "items": [
    { "product_variant_id": 12, "quantity_requested": 20 },
    { "product_variant_id": 15, "quantity_requested": 5 }
  ]
}
```

## PATCH `/inventory/transfers/{transfer}/status` — Request Body
```json
{
  "status": "picked",
  "items": [
    { "stock_transfer_item_id": 1, "quantity_sent": 20 }
  ]
}
```
> `items` is only required when transitioning to `in_transit` (to record `quantity_sent`)
> `quantity_received` is set on transition to `received`

## Response Shape — `GET /inventory/transfers/{transfer}`
```json
{
  "id": 4,
  "status": "in_transit",
  "from_store": { "id": 1, "name": "Main Branch" },
  "to_store": { "id": 2, "name": "North Branch" },
  "requested_by": { "id": 3, "name": "Ahmed K." },
  "items": [
    {
      "id": 1,
      "variant_label": "Size 42 / Black",
      "quantity_requested": 20,
      "quantity_sent": 20,
      "quantity_received": 0,
      "discrepancy": 0
    }
  ]
}
```

## Notes
- `discrepancy` is computed: `quantity_sent - quantity_received` (shown after completion)
- Cancel (`DELETE`) returns 422 if status is already `completed`
- All endpoints require auth + `stock.adjust` permission
