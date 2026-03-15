# Task 05 — Database: Stock Alerts

## New Migration

### `add_minimum_stock_level_to_product_variants_table`
```
$table->unsignedInteger('minimum_stock_level')->nullable()->after('cost_price');
```

## Relevant Existing Columns (no changes)
| Table | Column | Role in Alerts |
|---|---|---|
| `product_variants` | `minimum_stock_level` | Low-stock threshold (new) |
| `batches` | `expiry_date` | Expiry alert trigger |
| `batches` | `store_id` | Store-scoped alerts |
| `batches` | `status` | Only active/queued batches considered |
| `batches` | `remaining_quantity` | For stock aggregation |

## No New Tables
Alerts are computed on demand — no persistence layer required for v1.

## Key Query Patterns

**Low-stock alert query:**
```php
// Variants where aggregated stock < minimum_stock_level, per store
ProductVariant::query()
    ->whereNotNull('minimum_stock_level')
    ->whereHas('batches', function ($q) use ($storeId) {
        $q->whereIn('status', ['active', 'queued'])
          ->when($storeId, fn($q) => $q->where('store_id', $storeId));
    })
    ->withSum(['batches' => fn($q) => $q->whereIn('status', ['active','queued'])
        ->when($storeId, fn($q) => $q->where('store_id', $storeId))
    ], 'remaining_quantity')
    ->havingRaw('batches_sum_remaining_quantity < minimum_stock_level');
```

**Expiry alert query:**
```php
Batch::whereNotNull('expiry_date')
    ->whereIn('status', ['active', 'queued'])
    ->where('remaining_quantity', '>', 0)
    ->whereDate('expiry_date', '<=', now()->addDays($alertDays))
    ->when($storeId, fn($q) => $q->where('store_id', $storeId));
```

## Settings Reference
| Key | Type | Default | Description |
|---|---|---|---|
| `expiry_alert_days` | int | 30 | Trigger threshold for expiry alerts |

## Indexes Supporting Alert Queries
- `batches(store_id, status, expiry_date)` — expiry alert query
- `batches(product_variant_id, store_id, status)` — low-stock aggregation
- `product_variants(minimum_stock_level)` — partial index if using Postgres (nullable filter)
