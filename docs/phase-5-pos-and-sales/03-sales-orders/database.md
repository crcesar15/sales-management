# Database — Sales Orders

## Table: `sales_orders` (ALTER existing table)

The `sales_orders` table already exists but needs significant modifications. Apply these via a new migration (`alter_sales_orders_table_add_columns`):

### Existing Columns (Keep)
| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `customer_id` | `foreignId` nullable | FK → `customers.id`, nullOnDelete |
| `user_id` | `foreignId` | FK → `users.id` (cashier) |
| `notes` | `text` nullable | |
| `created_at` / `updated_at` | `timestamps` | |

### Existing Columns (Modify)
| Column | Current Type | New Type | Notes |
|---|---|---|---|
| `status` | `enum('draft','sent','paid','canceled')` | `enum('draft','sent','paid','held','cancelled')` | Add `held`, fix `canceled` → `cancelled` |
| `payment_method` | `enum('cash','credit_card','qr','stripe','transfer')` | `enum('cash','credit_card','qr','transfer')` | Remove `stripe`, informational/default |
| `sub_total` | `float(10)` | `decimal(12,2)` | Currency precision |
| `discount` | `float(10)` | `decimal(12,2)` | Currency precision |
| `total` | `float(10)` | `decimal(12,2)` | Currency precision |

### New Columns (Add)
| Column | Type | Notes |
|---|---|---|
| `store_id` | `foreignId` | FK → `stores.id` cascadeOnDelete |
| `discount_type` | `enum('flat','percentage')` | default 'flat' |
| `discount_value` | `decimal(10,2)` default 0 | Raw discount input |
| `tax_amount` | `decimal(12,2)` default 0 | Computed tax |
| `token` | `uuid` unique nullable | Auto-generated, for receipt URL |
| `cash_register_shift_id` | `foreignId` nullable | FK → `cash_register_shifts.id` nullOnDelete |

### Indexes (Add)
| Index | Column(s) | Reason |
|---|---|---|
| Index | `store_id, status` | Store-scoped status filtering |
| Index | `user_id` | Own-orders filtering |
| Index | `cash_register_shift_id` | Shift-based order queries |
| Unique | `token` | Receipt URL lookup |

## Table: `sales_order_items` (New)

| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `sales_order_id` | `foreignId` | FK → `sales_orders.id` cascadeOnDelete |
| `product_variant_id` | `foreignId` | FK → `product_variants.id` cascadeOnDelete |
| `sale_unit_id` | `foreignId` nullable | FK → `product_variant_units.id` nullOnDelete |
| `quantity` | `integer unsigned` | |
| `unit_price` | `decimal(12,2)` | Snapshot price at sale time |
| `conversion_factor` | `integer unsigned` default 1 | Snapshot from `product_variant_units.conversion_factor` |
| `line_total` | `decimal(12,2)` | `quantity × unit_price` |
| `created_at` / `updated_at` | `timestamps` | |

**Indexes:**
| Index | Column(s) | Reason |
|---|---|---|
| Index | `sales_order_id` | Order item lookup |
| Index | `product_variant_id` | Variant-based queries |

**Note:** `sale_unit_id` references `product_variant_units.id` (not `sale_units.id` — that table doesn't exist). The `product_variant_units` table has a `type` column where `type='sale'` identifies sale units.

## Table: `sales_order_payments` (New)

| Column | Type | Notes |
|---|---|---|
| `id` | `bigIncrements` | PK |
| `sales_order_id` | `foreignId` | FK → `sales_orders.id` cascadeOnDelete |
| `payment_method` | `enum('cash','credit_card','qr','transfer')` | |
| `amount` | `decimal(12,2)` | |
| `reference` | `varchar(255)` nullable | Card last 4, transfer ref, etc. |
| `created_at` / `updated_at` | `timestamps` | |

**Indexes:**
| Index | Column(s) | Reason |
|---|---|---|
| Index | `sales_order_id` | Order payment lookup |

## Relationships
```
SalesOrder belongsTo Customer (nullable)
SalesOrder belongsTo User (cashier)
SalesOrder belongsTo Store
SalesOrder belongsTo CashRegisterShift (nullable)
SalesOrder hasMany SalesOrderItem
SalesOrder hasMany SalesOrderPayment
SalesOrderItem belongsTo ProductVariant
SalesOrderItem belongsTo ProductVariantUnit (nullable, via sale_unit_id)
SalesOrderPayment belongsTo SalesOrder
CashRegisterShift hasMany SalesOrder (via cash_register_shift_id on sales_orders)
```

## Migration Notes
1. `alter_sales_orders_table_add_columns` — add `store_id`, `discount_type`, `discount_value`, `tax_amount`, `token`, `cash_register_shift_id`; change `float` columns to `decimal`; change `status` enum to add `held` and fix `canceled` → `cancelled`; change `payment_method` enum to remove `stripe`
2. `create_sales_order_items_table` — new table
3. `create_sales_order_payments_table` — new table
4. Since this is a development environment, the migration can use `DB::statement` for the enum changes, or `change()` with `doctrine/dbal` for column type modifications
5. The existing `sales_orders` table has 0 rows in development (no data to migrate), making the enum and column type changes safe

## PaymentMethod Enum Discrepancy
The `App\Enums\PaymentMethod` PHP enum currently defines:
```php
case BANK_TRANSFER = 'bank_transfer';
case CASH = 'cash';
case CHECK = 'check';
case CREDIT_CARD = 'credit_card';
```

This needs to be updated to match the database and POS requirements:
```php
case CASH = 'cash';
case CREDIT_CARD = 'credit_card';
case QR = 'qr';
case TRANSFER = 'transfer';
```

This is a code change to be done during implementation, not a migration concern.