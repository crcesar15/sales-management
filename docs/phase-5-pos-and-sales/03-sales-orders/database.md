# Database — Sales Orders

## Table: `sales_orders`

| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `customer_id` | `foreignId` nullable | FK → `customers.id`, nullOnDelete |
| `user_id` | `foreignId` | FK → `users.id` (cashier) |
| `store_id` | `foreignId` | FK → `stores.id` — **new migration** |
| `status` | `enum` | `draft\|sent\|paid\|cancelled` |
| `payment_method` | `enum` | `cash\|credit_card\|qr\|transfer` |
| `discount_type` | `enum` | `flat\|percentage` |
| `discount_value` | `decimal(10,2)` | Raw discount input |
| `sub_total` | `decimal(10,2)` | Sum of line totals before discount |
| `discount` | `decimal(10,2)` | Computed discount amount |
| `tax_amount` | `decimal(10,2)` | **New column** — computed tax |
| `total` | `decimal(10,2)` | Final amount |
| `notes` | `text` nullable | |
| `token` | `uuid` unique | Auto-generated, used for receipt URL |
| `created_at` / `updated_at` | `timestamps` | |

## Table: `sales_order_items`

| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `sales_order_id` | `foreignId` | FK → `sales_orders.id` CASCADE DELETE |
| `product_variant_id` | `foreignId` | FK → `product_variants.id` |
| `sale_unit_id` | `foreignId` nullable | FK → `sale_units.id` |
| `quantity` | `integer` | |
| `unit_price` | `decimal(10,2)` | Snapshot price at sale time |
| `conversion_factor` | `decimal(10,4)` | Snapshot from `sale_units.conversion_factor` |
| `line_total` | `decimal(10,2)` | `quantity × unit_price` |

## New Migrations Required
1. `create_sales_orders_table` — full table creation
2. `create_sales_order_items_table` — full table creation
3. `add_store_id_to_sales_orders_table` — if `sales_orders` already exists without it

## Key Indexes
| Table | Index | Columns |
|---|---|---|
| `sales_orders` | Index | `store_id, status` |
| `sales_orders` | Index | `user_id` |
| `sales_orders` | Unique | `token` |
| `sales_order_items` | Index | `sales_order_id` |

## Relationships
```
SalesOrder belongsTo Customer (nullable)
SalesOrder belongsTo User
SalesOrder belongsTo Store
SalesOrder hasMany SalesOrderItem
SalesOrderItem belongsTo ProductVariant
SalesOrderItem belongsTo SaleUnit (nullable)
```
