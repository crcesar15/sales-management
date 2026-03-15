# API — Sales Orders

## Endpoints

| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/sales-orders` | Paginated list (scoped by permission) | `sales.view` |
| `GET` | `/sales-orders/{order}` | Order detail | `sales.view` |
| `POST` | `/sales-orders` | Create manual order (draft) | `sales.manage` |
| `PUT` | `/sales-orders/{order}` | Update order (draft only) | `sales.manage` |
| `PATCH` | `/sales-orders/{order}/status` | Transition status | `sales.manage` |
| `DELETE` | `/sales-orders/{order}` | Cancel / delete draft | `sales.manage` |

> POS checkout (`POST /pos/checkout`) is a separate endpoint handled by `PosController`.

## Status Transition Rules
| From | To | Trigger |
|---|---|---|
| `draft` | `paid` | POS checkout |
| `draft` | `sent` | Manual: mark as sent |
| `sent` | `paid` | Manual: mark as paid |
| `draft\|sent\|paid` | `cancelled` | Manual cancellation |

## List Query Parameters
| Param | Type | Description |
|---|---|---|
| `search` | string | Customer name, order ID |
| `status` | string | Filter by status |
| `from` | date | `created_at` range start |
| `to` | date | `created_at` range end |
| `page` | int | Pagination |

## Inertia Page Props (List)
```json
{
  "orders": { "data": [...], "meta": { "total": 100 } },
  "filters": { "search": "", "status": "paid" },
  "canViewAll": true
}
```

## Order Detail Props (key fields)
```json
{
  "id": 88,
  "status": "paid",
  "customer": { "id": 1, "display_name": "Jane Doe" },
  "items": [
    { "product_name": "Apple Juice", "variant_sku": "AJ-500ML",
      "sale_unit_name": "Bottle", "quantity": 3, "unit_price": 25.00, "line_total": 75.00 }
  ],
  "sub_total": 75.00, "discount": 7.50, "tax_amount": 4.73, "total": 72.23
}
```
