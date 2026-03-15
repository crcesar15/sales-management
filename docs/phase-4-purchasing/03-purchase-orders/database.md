# Task 03: Purchase Orders — Database

## Table: `purchase_orders`

| Column                   | Type          | Notes                                              |
|--------------------------|---------------|----------------------------------------------------|
| id                       | bigIncrements | PK                                                 |
| user_id                  | foreignId     | FK → users                                         |
| vendor_id                | foreignId     | FK → vendors                                       |
| status                   | enum          | `draft`,`awaiting_approval`,`approved`,`sent`,`paid`,`cancelled` |
| order_date               | date          |                                                    |
| expected_arrival_date    | date, nullable|                                                    |
| sub_total                | float         | sum of line item totals                            |
| discount                 | float         | PO-level flat discount                             |
| total                    | float         | sub_total − discount                               |
| notes                    | text, nullable|                                                    |
| proof_of_payment_type    | varchar       | nullable                                           |
| proof_of_payment_number  | varchar       | nullable                                           |
| created_at               | timestamp     |                                                    |
| updated_at               | timestamp     |                                                    |

## Table: `purchase_order_product` (line items)

| Column              | Type          | Notes                              |
|---------------------|---------------|------------------------------------|
| id                  | bigIncrements | PK                                 |
| purchase_order_id   | foreignId     | FK → purchase_orders CASCADE       |
| product_variant_id  | foreignId     | FK → product_variants CASCADE      |
| quantity            | float         |                                    |
| price               | float         | snapshotted from catalog           |
| total               | float         | quantity × price                   |

## Key Indexes
```php
$table->index('status');
$table->index('vendor_id');
$table->index('order_date');
```

## Relationships

| From                    | To                    | Type      | Notes                           |
|-------------------------|-----------------------|-----------|---------------------------------|
| purchase_orders         | vendors               | belongsTo |                                 |
| purchase_orders         | users                 | belongsTo | created by                      |
| purchase_orders         | purchase_order_product| hasMany   | line items                      |
| purchase_order_product  | product_variants      | belongsTo |                                 |
| purchase_orders         | reception_orders      | hasMany   | Task 04                         |

## Notable Patterns
- Status transitions enforced in a `PurchaseOrderService`; never updated directly
- Totals recalculated server-side on every create/update (never trusted from client)
- Activity log records every status change with `causer` and old/new status
