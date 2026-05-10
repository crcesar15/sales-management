# Task 03: Catalog — Database

## No New Tables or Migrations

This module reuses the existing `catalog` table from Task 02. No schema changes are required.

## Existing Table: `catalog`

| Column                    | Type              | Notes                                    |
|---------------------------|-------------------|------------------------------------------|
| id                        | bigIncrements     | PK                                       |
| vendor_id                 | foreignId         | FK → vendors CASCADE                     |
| product_variant_id        | foreignId         | FK → product_variants CASCADE            |
| unit_id                   | foreignId         | FK → product_variant_units, nullable, nullOnDelete |
| price                     | float             | default catalog price                    |
| payment_terms             | varchar(15)       | nullable                                 |
| details                   | varchar(300)      | nullable                                 |
| status                    | enum              | `active`, `inactive`                     |
| minimum_order_quantity    | integer           | nullable                                 |
| lead_time_days            | integer           | nullable                                 |
| created_at                | timestamp         |                                          |
| updated_at                | timestamp         |                                          |

## Key Indexes (Existing)

- `unique(vendor_id, product_variant_id, unit_id)` — prevents duplicate vendor+variant+unit combos
- `index(status)` — for active filtering
- `index(unit_id)` — for unit lookups

## Relationships Used

| From     | To                    | Type      | Notes                           |
|----------|-----------------------|-----------|---------------------------------|
| catalog  | vendors               | belongsTo |                                 |
| catalog  | product_variants      | belongsTo | via product_variant_id          |
| catalog  | product_variant_units | belongsTo | via unit_id; nullable           |

## Query Patterns

**Product-centric listing:**
```sql
SELECT product_variants.*, catalog.*
FROM catalog
JOIN product_variants ON catalog.product_variant_id = product_variants.id
JOIN products ON product_variants.product_id = products.id
WHERE catalog.status = 'active'
ORDER BY products.name;
```

**Vendor comparison for a variant:**
```sql
SELECT catalog.*, vendors.fullname
FROM catalog
JOIN vendors ON catalog.vendor_id = vendors.id
WHERE catalog.product_variant_id = ?
ORDER BY catalog.price;
```