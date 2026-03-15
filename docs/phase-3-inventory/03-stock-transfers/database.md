# Task 03 — Database: Stock Transfers

## New Tables

### `stock_transfers`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `from_store_id` | bigint FK | → `stores` |
| `to_store_id` | bigint FK | → `stores` |
| `requested_by` | bigint FK | → `users` |
| `status` | enum | `requested`, `picked`, `in_transit`, `received`, `completed`, `cancelled` |
| `notes` | text nullable | |
| `cancelled_at` | timestamp nullable | Set on cancel |
| `completed_at` | timestamp nullable | Set on complete |
| `timestamps` | | |

### `stock_transfer_items`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `stock_transfer_id` | bigint FK | → `stock_transfers` |
| `product_variant_id` | bigint FK | → `product_variants` |
| `quantity_requested` | int | Original request |
| `quantity_sent` | int default 0 | Set when picked/in_transit |
| `quantity_received` | int default 0 | Set when received |
| `timestamps` | | |

## Key Indexes
| Index | Purpose |
|---|---|
| `stock_transfers(from_store_id, status)` | Outbound transfers per store |
| `stock_transfers(to_store_id, status)` | Inbound transfers per store |
| `stock_transfers(requested_by)` | Transfers by user |
| `stock_transfer_items(stock_transfer_id)` | Items for a transfer |
| `stock_transfer_items(product_variant_id)` | Transfers involving a variant |

## Relationships Summary
- `StockTransfer` belongsTo `Store` (from), `Store` (to), `User` (requestedBy)
- `StockTransfer` hasMany `StockTransferItem`
- `StockTransferItem` belongsTo `StockTransfer`, `ProductVariant`
- `StockTransfer` morphMany `Activity` (Spatie)

## Validation Rule
```php
Rule::different('from_store_id') // applied to to_store_id
```

## Completion Flow (DB side)
1. For each item: FIFO deduct `quantity_received` from source store batches
2. Create new `Batch` at destination: `initial_quantity = quantity_received`, `status = queued`
3. Update `stock_transfers.status = completed`, set `completed_at`
