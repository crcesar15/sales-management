# Task 02: Vendor Catalog — Database

## Existing Table: `catalog`

| Column              | Type              | Notes                              |
|---------------------|-------------------|------------------------------------|
| id                  | bigIncrements     | PK                                 |
| vendor_id           | foreignId         | FK → vendors CASCADE               |
| product_variant_id  | foreignId         | FK → product_variants CASCADE      |
| price               | float             | default catalog price              |
| payment_terms       | varchar(15)       | nullable                           |
| details             | varchar(300)      | nullable                           |
| status              | enum              | `active`, `inactive`               |
| created_at          | timestamp         |                                    |
| updated_at          | timestamp         |                                    |

## New Migration: `add_purchasing_columns_to_catalog_table`

```php
$table->foreignId('purchase_unit_id')->nullable()->constrained('measurement_units')->nullOnDelete();
$table->integer('conversion_factor')->default(1);
$table->integer('minimum_order_quantity')->nullable();
$table->integer('lead_time_days')->nullable();
```

## Key Indexes

```php
$table->unique(['vendor_id', 'product_variant_id']); // already exists or add here
$table->index('status');
$table->index('purchase_unit_id');
```

## Relationships

| From     | To                 | Type      | Notes                                  |
|----------|--------------------|-----------|----------------------------------------|
| catalog  | vendors            | belongsTo |                                        |
| catalog  | product_variants   | belongsTo |                                        |
| catalog  | measurement_units  | belongsTo | via purchase_unit_id; nullable         |

## Notable Patterns
- `conversion_factor` must be ≥ 1; validated at application level
- Unique constraint at DB level prevents duplicate vendor + variant pairs
- Deactivating a catalog entry (`status = inactive`) hides it from PO creation
- Price stored as `float`; consider rounding to 2 decimal places in application layer
- `conversion_factor` is read by reception orders at stock-update time
