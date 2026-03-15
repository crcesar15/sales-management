# Task 01 — Database: Stock Overview

## Relevant Tables

### `batches` (existing + migration)
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `product_variant_id` | bigint FK | → `product_variants` |
| `store_id` | bigint FK | → `stores` **NEW** |
| `reception_order_id` | bigint FK | → `reception_orders` |
| `expiry_date` | date nullable | |
| `initial_quantity` | int | Set at reception |
| `remaining_quantity` | int | Decremented on sales/transfers |
| `missing_quantity` | int default 0 | From audits |
| `sold_quantity` | int default 0 | Incremented by sales |
| `transferred_quantity` | int default 0 | Incremented by transfers out |
| `status` | enum | `queued`, `active`, `closed` |
| `timestamps` | | |

### `product_variants` (referenced)
| Column | Type | Notes |
|---|---|---|
| `minimum_stock_level` | int nullable | Added in Task 05 migration |

## New Migration
```
add_store_id_to_batches_table
  $table->foreignId('store_id')->after('reception_order_id')->constrained()->cascadeOnDelete();
```

## Key Indexes
- `batches(store_id, status)` — filters active/queued batches per store
- `batches(product_variant_id, store_id, status)` — stock aggregation query
- `batches(expiry_date)` — expiry alert queries (Task 05)

## Relationships Summary
- `Batch` belongsTo `ProductVariant`, `Store`, `ReceptionOrder`
- `ProductVariant` hasMany `Batch`
- `Store` hasMany `Batch`

## Key Query Pattern
```php
// Stock per variant per store
Batch::query()
    ->whereIn('status', ['active', 'queued'])
    ->selectRaw('product_variant_id, store_id, SUM(remaining_quantity) as stock')
    ->groupBy('product_variant_id', 'store_id');
```

## Notable Patterns
- Stock is never stored directly on `product_variants` — always derived from batches
- Closing a batch sets `status = closed`; it is excluded from stock totals
- `store_id` on batches is the authoritative location of that stock
