# Database — Refunds & Returns

## Table: `refunds`

| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `sales_order_id` | `foreignId` | FK → `sales_orders.id` |
| `user_id` | `foreignId` | FK → `users.id` (requester) |
| `store_id` | `foreignId` | FK → `stores.id` |
| `reason` | `text` | Required, explains return reason |
| `total_refund` | `decimal(10,2)` | Sum of `refund_items.line_refund` |
| `status` | `enum` | `pending\|approved\|rejected\|completed` |
| `created_at` / `updated_at` | `timestamps` | |

## Table: `refund_items`

| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `refund_id` | `foreignId` | FK → `refunds.id` CASCADE DELETE |
| `sales_order_item_id` | `foreignId` | FK → `sales_order_items.id` |
| `quantity_returned` | `integer` | Must be ≤ remaining returnable qty |
| `line_refund` | `decimal(10,2)` | `quantity_returned × unit_price` |

## New Migrations Required
1. `create_refunds_table`
2. `create_refund_items_table`

## Key Indexes
| Table | Index | Columns |
|---|---|---|
| `refunds` | Index | `sales_order_id` |
| `refunds` | Index | `store_id, status` |
| `refund_items` | Index | `refund_id` |
| `refund_items` | Index | `sales_order_item_id` |

## Relationships
```
Refund belongsTo SalesOrder
Refund belongsTo User
Refund belongsTo Store
Refund hasMany RefundItem
RefundItem belongsTo SalesOrderItem
RefundItem belongsTo Refund
SalesOrderItem hasMany RefundItems  ← for qty validation
```

## Returnability Calculation
```sql
-- Max returnable qty for a sales_order_item:
original_qty - SUM(refund_items.quantity_returned WHERE refund.status IN ('approved','completed'))
```
