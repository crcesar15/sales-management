# Database — Receipts

## No New Tables Required

Receipts are generated on-the-fly from existing data.

## Column Added to `sales_orders`
| Column | Type | Notes |
|---|---|---|
| `token` | `uuid` unique | Auto-generated on order creation. Added in Task 03. |

> If `sales_orders` already existed without `token`, add via:
> `add_token_to_sales_orders_table` migration

```php
$table->uuid('token')->unique()->after('notes');
```

## Settings Keys Used for Receipt

| Key | Group | Description |
|---|---|---|
| `store_name` | `general` | Displayed as receipt header |
| `store_address` | `general` | Printed under store name |
| `store_phone` | `general` | Printed on receipt |
| `receipt_header` | `receipt` | Custom text above items |
| `receipt_footer` | `receipt` | Custom text below total |
| `store_logo` | `general` | Media/path for logo image |

## Data Loaded for Receipt View
```
SalesOrder
  → customer (nullable)
  → user (cashier name)
  → store
  → items
      → productVariant → product (name)
      → saleUnit (name)
```

## Notable Patterns
- Receipt is read-only — no DB writes on receipt page load
- Token lookup: `SalesOrder::where('token', $token)->firstOrFail()`
- If product/variant is deleted after sale, eager load `withTrashed()` (if soft-deleted) or store name snapshot on `sales_order_items`
