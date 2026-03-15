# Phase 6 — Task 02: Sales Reports — API

## Endpoints

| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/reports/sales` | Render sales report page (Inertia) | `reports.view_own` or `reports.view_all` |
| `GET` | `/reports/sales/metrics` | Return aggregate metrics JSON (partial reload) | Same |

> Both endpoints apply the same permission-based scoping. A single Inertia page with partial reloads is preferred over two separate routes.

## Query Parameters (Filters)

| Param | Type | Notes |
|---|---|---|
| `store_id` | integer | `view_all` only |
| `user_id` | integer | `view_all` only; `view_own` forces current user |
| `product_variant_id` | integer | Filter by specific variant |
| `date_from` | `Y-m-d` | Required for bounded queries |
| `date_to` | `Y-m-d` | Defaults to today |
| `payment_method` | string | `cash`, `card`, `bank_transfer`, etc. |
| `status` | string | `paid`, `refunded`, `cancelled` |
| `page` | integer | Pagination |

## Response Shape

**Inertia page props**
```json
{
  "filters": { "store_id": null, "date_from": "2025-11-01", "date_to": "2025-11-30" },
  "metrics": {
    "order_count": 142,
    "total_revenue": 87430.00,
    "avg_order_value": 615.70,
    "total_discount": 3200.00,
    "total_tax": 8743.00,
    "total_refunds": 1250.00
  },
  "orders": {
    "data": [
      {
        "id": 501,
        "created_at": "2025-11-15T10:22:00Z",
        "customer": { "name": "John Doe" },
        "cashier": { "name": "Alice Smith" },
        "items_count": 3,
        "total": 450.00,
        "status": "paid"
      }
    ],
    "current_page": 1,
    "last_page": 6,
    "total": 142
  }
}
```

## Notes
- Filter params serialised into URL query string so reports are shareable/bookmarkable
- `metrics` and `orders` can be partial-reloaded independently using Inertia `only`
- No dedicated export endpoint in v1
