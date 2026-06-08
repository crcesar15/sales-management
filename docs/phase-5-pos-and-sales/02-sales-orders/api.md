# API — Sales Orders

> **Note**: POS checkout, hold, and resume endpoints are defined in Task 03 (POS Interface) — they use `SalesOrderService` methods from this task (`create`, `holdOrder`, `resumeOrder`, `calculateTotals`).

> **Note**: API routes and `Api\SalesOrderController` are deferred to a future task. This document describes the Inertia (web) routes that have been implemented, with the API routes documented for future reference.

## Web Routes (Inertia) ✅ Implemented
All routes registered in `routes/web.php` under the `auth` middleware group.

| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/sales-orders` | Paginated order list | `sales.view` |
| `GET` | `/sales-orders/create` | Create manual order form | `sales.manage` |
| `POST` | `/sales-orders` | Store manual order | `sales.manage` |
| `GET` | `/sales-orders/{salesOrder}` | Order detail | `sales.view` |
| `GET` | `/sales-orders/{salesOrder}/edit` | Edit draft order | `sales.manage` |
| `PUT` | `/sales-orders/{salesOrder}` | Update draft order | `sales.manage` |
| `PATCH` | `/sales-orders/{salesOrder}/status` | Transition status | `sales.manage` |

> POS checkout (`POST /pos/checkout`), hold (`POST /pos/hold`), and resume (`POST /pos/resume/{order}`) are separate endpoints handled by `PosController`.

## API Routes (JSON) — ⏳ Deferred
All under `routes/api.php` with `v1` prefix and `auth:sanctum`. These will be implemented in a future task.

| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/api/v1/sales-orders` | Paginated list (scoped by permission) | `sales.view` |
| `GET` | `/api/v1/sales-orders/{salesOrder}` | Order detail | `sales.view` |
| `POST` | `/api/v1/sales-orders` | Create manual order | `sales.manage` |
| `PUT` | `/api/v1/sales-orders/{salesOrder}` | Update draft order | `sales.manage` |
| `PATCH` | `/api/v1/sales-orders/{salesOrder}/status` | Transition status | `sales.manage` |

## Status Transition Rules
| From | To | Trigger |
|---|---|---|
| `draft` | `paid` | POS checkout |
| `draft` | `sent` | Manual: mark as sent |
| `draft` | `held` | POS: hold order |
| `held` | `draft` | POS: resume order |
| `sent` | `paid` | Manual: mark as paid |
| any | `cancelled` | Manual cancellation |

## List Query Parameters
| Param | Type | Description |
|---|---|---|
| `search` | string | Customer name, order ID |
| `status` | string | Filter by status (draft, sent, paid, held, cancelled) |
| `from` | date | `created_at` range start |
| `to` | date | `created_at` range end |
| `page` | int | Pagination |

## Inertia Page Props (List)
```json
{
  "orders": {
    "data": [
      {
        "id": 88,
        "status": "paid",
        "discount_type": "flat",
        "discount_value": 15.0,
        "sub_total": 150.00,
        "discount": 15.00,
        "tax_amount": 9.45,
        "total": 144.45,
        "customer": { "id": 1, "display_name": "Jane Doe" },
        "user": { "id": 2, "full_name": "John Sales" },
        "cash_register_shift_id": 5,
        "created_at": "2026-05-31T14:30:00Z"
      }
    ],
    "meta": { "total": 100, "current_page": 1, "last_page": 5, "per_page": 20 }
  },
  "filters": { "search": "", "status": "paid" },
  "canViewAll": true
}
```

> **Note**: `payment_method` is NOT a column on `sales_orders`. The list view does not include it. For display purposes, derive the primary payment method from the first `sales_order_payments` record when loading the detail view.

## Order Detail Props
```json
{
  "id": 88,
  "status": "paid",
  "discount_type": "flat",
  "discount_value": 15.0,
  "sub_total": 150.00,
  "discount": 15.00,
  "tax_amount": 9.45,
  "total": 144.45,
  "token": "550e8400-e29b-41d4-a716-446655440000",
  "notes": null,
  "customer": { "id": 1, "display_name": "Jane Doe", "email": "jane@example.com" },
  "user": { "id": 2, "full_name": "John Sales" },
  "store": { "id": 1, "name": "Main Store" },
  "cash_register_shift": { "id": 5, "cash_register": { "name": "Register 1" }, "opened_at": "2026-05-31T08:00:00Z" },
  "items": [
    {
      "id": 1,
      "product_variant_id": 10,
      "product_variant": { "sku": "AJ-500ML", "product": { "name": "Apple Juice" } },
      "sale_unit_id": 1,
      "sale_unit": { "name": "Bottle" },
      "quantity": 3,
      "unit_price": 25.00,
      "conversion_factor": 1,
      "line_total": 75.00
    }
  ],
  "payments": [
    { "id": 1, "payment_method": "cash", "amount": 94.45, "reference": null },
    { "id": 2, "payment_method": "credit_card", "amount": 50.00, "reference": "****4242" }
  ],
  "created_at": "2026-05-31T14:30:00Z"
}
```

## Validation Rules (Create Manual Order)
| Field | Rules |
|---|---|
| `customer_id` | `nullable\|exists:customers,id` |
| `discount_type` | `required\|in:flat,percentage` |
| `discount_value` | `required\|numeric\|min:0` |
| `notes` | `nullable\|string` |
| `items` | `required\|array\|min:1` |
| `items.*.product_variant_id` | `required\|exists:product_variants,id` |
| `items.*.sale_unit_id` | `nullable\|exists:product_variant_units,id` |
| `items.*.quantity` | `required\|integer\|min:1` |
| `items.*.unit_price` | `required\|numeric\|min:0` |
| `payments` | `required\|array\|min:1` |
| `payments.*.payment_method` | `required\|in:cash,credit_card,qr,transfer` |
| `payments.*.amount` | `required\|numeric\|min:0.01` |
| `payments.*.reference` | `nullable\|string\|max:255` |

> **Note**: `payment_method` is NOT a field on the order header. It only exists on individual `sales_order_payments` records. The `StoreSalesOrderRequest` includes a `withValidator()` check that verifies the payments total equals the calculated order total.

## Validation Rules (Transition Status)
| Field | Rules |
|---|---|
| `status` | `required\|in:draft,sent,paid,held,cancelled` |

Custom validation: the transition must be allowed per the status transition map. Invalid transitions return 422 with a message like `"Cannot transition from paid to draft"`.

## Notes
- POS checkout endpoint (`POST /pos/checkout`) is documented in `03-pos-interface/api.md`
- `sales_order_payments` is included in order detail responses via `whenLoaded('payments')`
- `cash_register_shift_id` is null for manually created orders (non-POS)
- `payment_method` is NOT stored on `sales_orders`; the actual payment breakdown is in `sales_order_payments`. If a display value is needed for the order list, derive it from the first payment record.
- API routes (`Api\SalesOrderController`) are deferred to a future task