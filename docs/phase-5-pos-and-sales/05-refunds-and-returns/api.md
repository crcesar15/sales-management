# API — Refunds & Returns

## Endpoints

| Method | Path | Description | Permission |
|---|---|---|---|
| `GET` | `/refunds` | Paginated refund list | `refunds.manage` or own |
| `GET` | `/refunds/{refund}` | Refund detail | `refunds.manage` or own |
| `POST` | `/sales-orders/{order}/refunds` | Create refund request | `sales.create` |
| `PATCH` | `/refunds/{refund}/status` | Transition status | `refunds.manage` |

## Status Transition Rules
| From | To | Who |
|---|---|---|
| `pending` | `approved` | `refunds.manage` |
| `pending` | `rejected` | `refunds.manage` |
| `approved` | `completed` | `refunds.manage` |
| `approved` | `rejected` | `refunds.manage` |

## Create Refund Request Body
```json
{
  "reason": "Item damaged on delivery",
  "items": [
    { "sales_order_item_id": 5, "quantity_returned": 2 },
    { "sales_order_item_id": 6, "quantity_returned": 1 }
  ]
}
```

## Validation Rules (Create)
| Field | Rules |
|---|---|
| `reason` | `required\|string\|max:1000` |
| `items` | `required\|array\|min:1` |
| `items.*.sales_order_item_id` | `required\|exists:sales_order_items,id\|belongs to order` |
| `items.*.quantity_returned` | `required\|integer\|min:1\|≤ returnable qty` |

## Status Update Body
```json
{ "status": "approved" }
```

## Refund Detail Response (key fields)
```json
{
  "id": 12,
  "sales_order_id": 88,
  "status": "pending",
  "reason": "Item damaged",
  "total_refund": 50.00,
  "items": [
    { "product_name": "Apple Juice", "variant_sku": "AJ-500ML",
      "quantity_returned": 2, "line_refund": 50.00 }
  ]
}
```
