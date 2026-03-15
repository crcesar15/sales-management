# API — POS Interface

## Endpoints

| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/pos` | Load POS page (Inertia) | `sales.create` |
| `GET` | `/pos/products/search` | Product typeahead (JSON) | `sales.create` |
| `POST` | `/pos/checkout` | Submit cart → create order | `sales.create` |

## Product Search Response
```
GET /pos/products/search?q=apple
GET /pos/products/search?q=ABC123   (SKU or barcode)
```
```json
[
  {
    "id": 5,
    "name": "Apple Juice",
    "variants": [
      {
        "id": 12,
        "sku": "AJ-500ML",
        "barcode": "8850999123456",
        "sale_units": [
          { "id": 1, "name": "Bottle", "conversion_factor": 1, "price": 25.00 },
          { "id": 2, "name": "Case (12)", "conversion_factor": 12, "price": 280.00 }
        ]
      }
    ]
  }
]
```

## Checkout Request Body
```json
{
  "customer_id": null,
  "payment_method": "cash",
  "discount_type": "percentage",
  "discount_value": 10,
  "notes": "",
  "items": [
    {
      "product_variant_id": 12,
      "sale_unit_id": 1,
      "quantity": 3,
      "unit_price": 25.00
    }
  ]
}
```

## Checkout Response
```json
{
  "sales_order_id": 88,
  "receipt_token": "uuid-...",
  "total": 67.50
}
```

## Validation Rules (Checkout)
| Field | Rules |
|---|---|
| `customer_id` | `nullable\|exists:customers,id` |
| `payment_method` | `required\|in:cash,credit_card,qr,transfer` |
| `discount_type` | `required\|in:flat,percentage` |
| `discount_value` | `required\|numeric\|min:0` |
| `items` | `required\|array\|min:1` |
| `items.*.product_variant_id` | `required\|exists:product_variants,id` |
| `items.*.quantity` | `required\|integer\|min:1` |
| `items.*.unit_price` | `required\|numeric\|min:0` |
