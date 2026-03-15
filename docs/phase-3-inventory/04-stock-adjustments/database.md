# Task 04 — Database: Stock Adjustments

## New Table: `stock_adjustments`
| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `product_variant_id` | bigint FK | → `product_variants` |
| `store_id` | bigint FK | → `stores` |
| `user_id` | bigint FK | → `users` |
| `batch_id` | bigint FK nullable | → `batches` (target batch) |
| `quantity_change` | int | Positive or negative delta |
| `reason` | enum | `physical_audit`, `robbery`, `expiry`, `damage`, `correction`, `other` |
| `notes` | text nullable | Free-text detail |
| `timestamps` | | |

## No Changes to Existing Tables
The `batches.remaining_quantity` is updated as a side effect of applying the adjustment.
The `stock_adjustments` row is the audit record; the batch holds the current state.

## Key Indexes
| Index | Purpose |
|---|---|
| `stock_adjustments(store_id)` | Filter by store |
| `stock_adjustments(user_id)` | Sales Rep self-view |
| `stock_adjustments(product_variant_id)` | Adjustments for a variant |
| `stock_adjustments(batch_id)` | Adjustments per batch |
| `stock_adjustments(reason)` | Reporting by reason type |
| `stock_adjustments(created_at)` | Date-range reports |

## Relationships Summary
- `StockAdjustment` belongsTo `ProductVariant`, `Store`, `User`, `Batch` (nullable)
- `Batch` hasMany `StockAdjustment`
- `StockAdjustment` morphMany `Activity` (Spatie, for extra audit breadcrumb)

## Constraint Pattern
```php
// In service, before applying:
if ($batch->remaining_quantity + $quantityChange < 0) {
    throw new InsufficientStockException();
}
```

## Notable Patterns
- `quantity_change` is signed: `-10` means stock reduced by 10
- The `stock_adjustments` table is append-only — no updates or soft deletes
- Reporting: SUM(`quantity_change`) by reason gives loss analysis data
