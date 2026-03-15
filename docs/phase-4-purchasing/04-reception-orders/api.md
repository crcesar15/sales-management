# Task 04: Reception Orders — API

## Endpoints

| Method | Path                                  | Description                        | Permission               |
|--------|---------------------------------------|------------------------------------|--------------------------|
| GET    | `/reception-orders`                   | Paginated list                     | reception_orders.manage  |
| POST   | `/reception-orders`                   | Create reception order             | reception_orders.manage  |
| GET    | `/reception-orders/{ro}`              | Show reception with line items     | reception_orders.manage  |
| PUT    | `/reception-orders/{ro}`              | Update pending/uncompleted         | reception_orders.manage  |
| POST   | `/reception-orders/{ro}/complete`     | Complete and update stock          | reception_orders.manage  |
| POST   | `/reception-orders/{ro}/cancel`       | Cancel reception order             | reception_orders.manage  |
| GET    | `/purchase-orders/{po}/receptions`    | List receptions for a PO           | reception_orders.manage  |

## Request Body — POST `/reception-orders`

```json
{
  "purchase_order_id": 7,
  "store_id": 2,
  "reception_date": "2026-03-20",
  "notes": "Partial delivery — 2 items missing",
  "items": [
    {
      "product_variant_id": 42,
      "quantity": 5,
      "price": 24.50,
      "expiry_date": "2027-06-01"
    }
  ]
}
```

## Request Body — POST `/{ro}/complete`

```json
{}
```
> Completion requires no body — triggers stock update and batch creation server-side.

## Response — Reception Resource

```json
{
  "id": 3,
  "purchase_order_id": 7,
  "store": { "id": 2, "name": "Downtown Store" },
  "vendor": { "id": 1, "fullname": "Acme Supplies" },
  "status": "completed",
  "reception_date": "2026-03-20",
  "items": [
    { "product_variant_id": 42, "quantity": 5, "price": 24.50, "total": 122.50 }
  ]
}
```

## Error Responses

| Status | Scenario                                                    |
|--------|-------------------------------------------------------------|
| 422    | PO not in `approved` or `sent` status                       |
| 422    | Store not found or inactive                                 |
| 409    | Completing an already-completed or cancelled reception      |
| 403    | Missing `reception_orders.manage` permission               |
