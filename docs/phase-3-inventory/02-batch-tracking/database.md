# Task 02 — Database: Batch Tracking

## Relevant Tables

### `batches` (full schema)
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `product_variant_id` | bigint FK | → `product_variants` |
| `store_id` | bigint FK | → `stores` (added Task 01) |
| `reception_order_id` | bigint FK | → `reception_orders` |
| `expiry_date` | date nullable | Set at reception, immutable |
| `initial_quantity` | int | Total received |
| `remaining_quantity` | int | Decremented on sales/transfers |
| `missing_quantity` | int default 0 | Audit discrepancies |
| `sold_quantity` | int default 0 | Phase 5 increments |
| `transferred_quantity` | int default 0 | Task 03 increments |
| `status` | enum(`queued`,`active`,`closed`) | Lifecycle state |
| `timestamps` | | |

### `settings` (referenced)
| Column | Purpose |
|---|---|
| `expiry_alert_days` | Days-before-expiry threshold for alerts |

## No New Migrations in This Task
> `store_id` was added in Task 01. No new columns required for batch tracking.

## Key Indexes
| Index | Purpose |
|---|---|
| `batches(store_id, status)` | Filter active batches per store |
| `batches(product_variant_id, status, created_at)` | FIFO selection |
| `batches(expiry_date, status)` | Expiry alert queries |

## FIFO Batch Selection Pattern
```php
Batch::where('product_variant_id', $variantId)
    ->where('store_id', $storeId)
    ->whereIn('status', ['queued', 'active'])
    ->where('remaining_quantity', '>', 0)
    ->orderBy('created_at', 'asc')
    ->first();
```

## Relationships Summary
- `Batch` belongsTo `ProductVariant`, `Store`, `ReceptionOrder`
- `Batch` hasMany `StockAdjustment` (Task 04)
- `Batch` belongsToMany via `StockTransferItem` (Task 03, indirectly)
- `Batch` morphMany `Activity` (Spatie activity log)
