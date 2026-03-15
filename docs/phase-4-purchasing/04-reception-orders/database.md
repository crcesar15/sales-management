# Task 04: Reception Orders — Database

## Table: `reception_orders`

| Column              | Type          | Notes                                          |
|---------------------|---------------|------------------------------------------------|
| id                  | bigIncrements | PK                                             |
| purchase_order_id   | foreignId     | FK → purchase_orders                           |
| user_id             | foreignId     | FK → users                                     |
| vendor_id           | foreignId     | FK → vendors                                   |
| store_id            | foreignId     | FK → stores **(new column — see migration)**   |
| reception_date      | date          |                                                |
| notes               | text, nullable|                                                |
| status              | enum          | `pending`, `uncompleted`, `completed`, `cancelled` |
| created_at          | timestamp     |                                                |
| updated_at          | timestamp     |                                                |

## Table: `reception_order_product` (line items)

| Column              | Type          | Notes                                |
|---------------------|---------------|--------------------------------------|
| id                  | bigIncrements | PK                                   |
| reception_order_id  | foreignId     | FK → reception_orders CASCADE        |
| product_variant_id  | foreignId     | FK → product_variants                |
| quantity            | float         | quantity in purchase units           |
| price               | float         | reference price                      |
| total               | float         | quantity × price                     |
| expiry_date         | date, nullable| optional per-batch expiry            |

> `expiry_date` may need to be added if not already on this table.

## New Migration: `add_store_id_to_reception_orders_table`
```php
$table->foreignId('store_id')->after('vendor_id')->constrained('stores');
```

## Key Indexes
```php
$table->index('purchase_order_id');
$table->index('store_id');
$table->index('status');
```

## Relationships

| From                     | To                    | Type      | Notes                         |
|--------------------------|-----------------------|-----------|-------------------------------|
| reception_orders         | purchase_orders       | belongsTo |                               |
| reception_orders         | stores                | belongsTo | destination store             |
| reception_orders         | vendors               | belongsTo |                               |
| reception_orders         | reception_order_product | hasMany |                               |
| reception_order_product  | product_variants      | belongsTo |                               |

## Notable Patterns
- Stock update and batch creation happen atomically in a DB transaction on completion
- `conversion_factor` is fetched from `catalog` at completion time (not stored on reception)
- Batches table receives one row per reception line item upon completion
