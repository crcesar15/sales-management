# API — POS Interface

## Endpoints
All POS endpoints require `sales.create` permission and `auth:sanctum` middleware (API) or `auth` middleware (web).

### Web Route (Inertia Page)
| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/pos` | Load POS page (Inertia) | `sales.create` |

### API Routes (JSON)
All under `routes/api.php` with `v1` prefix and `auth:sanctum`.

| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/pos/products/search` | Product typeahead for POS | `sales.create` |
| `POST` | `/pos/checkout` | Create paid order from cart | `sales.create` |
| `POST` | `/pos/hold` | Create held order (park cart) | `sales.create` |
| `GET` | `/pos/held-orders` | List held orders for current shift | `sales.create` |
| `POST` | `/pos/resume/{salesOrder}` | Resume a held order | `sales.create` |

## Product Search Response
```
GET /api/v1/pos/products/search?q=apple
```
Returns array of products, each with variants and their sale units:
```json
[
  {
    "id": 1,
    "name": "Apple Juice",
    "variants": [
      {
        "id": 10,
        "sku": "AJ-500ML",
        "barcode": "1234567890128",
        "price": 25.00,
        "stock": 150,
        "sale_units": [
          { "id": 1, "name": "Bottle", "conversion_factor": 1, "price": 25.00 },
          { "id": 2, "name": "Case (12)", "conversion_factor": 12, "price": 240.00 }
        ]
      }
    ]
  }
]
```
- Max 15 products returned
- Barcode exact match first, then LIKE fallback on `products.name` and `product_variants.sku`
- Only `active` sale units returned (`product_variant_units` where `type='sale'` and `status='active'`)
- `stock` is computed from `SUM(batches.remaining_quantity)` for `active` batches in the user's store

## Checkout Request Body
```json
{
  "customer_id": null,
  "cash_register_shift_id": 5,
  "discount_type": "flat",
  "discount_value": 15.00,
  "notes": "Walk-in purchase",
  "items": [
    {
      "product_variant_id": 10,
      "sale_unit_id": 1,
      "quantity": 3,
      "unit_price": 25.00
    },
    {
      "product_variant_id": 11,
      "sale_unit_id": null,
      "quantity": 2,
      "unit_price": 12.50
    }
  ],
  "payments": [
    { "payment_method": "cash", "amount": 94.45, "reference": null },
    { "payment_method": "credit_card", "amount": 50.00, "reference": "****4242" }
  ]
}
```

### Validation Rules
| Field | Rules |
|---|---|
| `customer_id` | `nullable\|exists:customers,id` |
| `cash_register_shift_id` | `required\|exists:cash_register_shifts,id` |
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

**Custom validation (in `withValidator()`):**
- `cash_register_shift_id` must reference an open shift belonging to the authenticated user
- Sum of `payments.*.amount` must equal the server-computed order total (within 0.01 tolerance)

## Checkout Response
```json
{
  "sales_order_id": 88,
  "receipt_token": "550e8400-e29b-41d4-a716-446655440000",
  "total": 144.45
}
```

## Hold Order Request Body
```json
{
  "customer_id": null,
  "cash_register_shift_id": 5,
  "discount_type": "flat",
  "discount_value": 15.00,
  "notes": "Customer will return",
  "items": [
    {
      "product_variant_id": 10,
      "sale_unit_id": 1,
      "quantity": 3,
      "unit_price": 25.00
    }
  ]
}
```
- No `payments` array — held orders have no payments
- Creates `sales_order` with `status = 'held'`
- Items are stored in `sales_order_items` but NO stock deduction occurs
- Returns `sales_order_id` and `token`

## Held Orders Response
```
GET /api/v1/pos/held-orders
```
```json
{
  "data": [
    {
      "id": 85,
      "token": "550e8400-...",
      "customer_name": "Walk-in",
      "total": 144.45,
      "item_count": 2,
      "created_at": "2026-05-31T14:30:00Z"
    }
  ]
}
```
- Filtered to `status = 'held'` AND `cash_register_shift_id` matching the user's current open shift

## Resume Order Response
```
POST /api/v1/pos/resume/{salesOrder}
```
```json
{
  "data": {
    "id": 85,
    "status": "draft",
    "customer_id": null,
    "discount_type": "flat",
    "discount_value": 15.00,
    "items": [
      {
        "product_variant_id": 10,
        "sale_unit_id": 1,
        "quantity": 3,
        "unit_price": 25.00,
        "conversion_factor": 1,
        "product_variant": {
          "id": 10,
          "sku": "AJ-500ML",
          "product": { "name": "Apple Juice" }
        },
        "sale_unit": { "id": 1, "name": "Bottle" }
      }
    ]
  }
}
```
- Transitions order from `held` to `draft`
- Returns full order with items, product variant names, and sale unit names for cart population

## Error Responses
- **422**: Insufficient stock for one or more variants (no partial commit — full rollback via `DB::transaction`)
- **422**: No open shift for the authenticated user
- **422**: Shift does not belong to the authenticated user
- **422**: Payment total does not match order total
- **422**: Order not in `held` status when attempting resume
- **403**: User lacks `sales.create` permission