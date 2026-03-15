# Task 03: Purchase Orders — API

## Endpoints

| Method | Path                             | Description                       | Permission              |
|--------|----------------------------------|-----------------------------------|-------------------------|
| GET    | `/purchase-orders`               | Paginated list                    | purchase_orders.create  |
| POST   | `/purchase-orders`               | Create draft PO                   | purchase_orders.create  |
| GET    | `/purchase-orders/{po}`          | Show PO with line items           | purchase_orders.create  |
| PUT    | `/purchase-orders/{po}`          | Update draft PO                   | purchase_orders.create  |
| POST   | `/purchase-orders/{po}/submit`   | draft → awaiting_approval         | purchase_orders.create  |
| POST   | `/purchase-orders/{po}/approve`  | awaiting_approval → approved      | purchase_orders.approve |
| POST   | `/purchase-orders/{po}/send`     | approved → sent                   | purchase_orders.approve |
| POST   | `/purchase-orders/{po}/pay`      | sent → paid                       | purchase_orders.approve |
| POST   | `/purchase-orders/{po}/cancel`   | Cancel (draft/awaiting/approved)  | purchase_orders.create  |

## Request Body — POST `/purchase-orders`

```json
{
  "vendor_id": 1,
  "order_date": "2026-03-15",
  "expected_arrival_date": "2026-03-22",
  "notes": "Urgent order",
  "discount": 50.00,
  "items": [
    { "product_variant_id": 42, "quantity": 10 }
  ]
}
```
> `price` is NOT sent by client — pulled from active catalog entry server-side.

## Response — PO Resource

```json
{
  "id": 7,
  "vendor": { "id": 1, "fullname": "Acme Supplies" },
  "status": "draft",
  "order_date": "2026-03-15",
  "sub_total": 245.00,
  "discount": 50.00,
  "total": 195.00,
  "items": [
    { "product_variant_id": 42, "quantity": 10, "price": 24.50, "total": 245.00 }
  ]
}
```

## Error Responses

| Status | Scenario                                              |
|--------|-------------------------------------------------------|
| 422    | Item not in vendor's active catalog                   |
| 409    | Invalid status transition attempted                   |
| 403    | Missing required permission for action                |
| 422    | Attempting to cancel a `sent` or `paid` PO            |
