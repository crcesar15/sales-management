# Phase 6 — Task 03: Inventory Reports — Database

## No New Migrations Required

## Tables Consumed

| Table | Key Columns | Section |
|---|---|---|
| `product_variants` | `id`, `product_id`, `sku`, `stock_quantity`, `minimum_stock_level`, `store_id` | Stock Levels |
| `products` | `id`, `name`, `category_id`, `brand_id` | Stock Levels |
| `categories` | `id`, `name` | Stock Levels filter |
| `brands` | `id`, `name` | Stock Levels filter |
| `stock_batches` | `id`, `product_variant_id`, `store_id`, `expiry_date`, `quantity`, `status` | Batch Status |
| `stock_adjustments` | `id`, `product_variant_id`, `store_id`, `quantity_change`, `reason`, `created_at`, `user_id` | Movement |
| `stock_transfer_items` | `id`, `transfer_id`, `product_variant_id`, `quantity`, `direction` | Movement |
| `stock_transfers` | `id`, `from_store_id`, `to_store_id`, `created_at` | Movement |
| `reception_order_product` | `id`, `reception_order_id`, `product_variant_id`, `quantity_received` | Movement |
| `reception_orders` | `id`, `store_id`, `created_at` | Movement |
| `sales_order_items` | `id`, `sales_order_id`, `product_variant_id`, `quantity` | Movement |
| `sales_orders` | `id`, `store_id`, `created_at`, `status` | Movement |

## Key Query Patterns

**Low-stock filter**
```php
ProductVariant::whereColumn('stock_quantity', '<', 'minimum_stock_level')
```

**Batch expiry filter**
```php
StockBatch::whereBetween('expiry_date', [$from, $to])
    ->where('status', $status)
    ->where('store_id', $storeId);
```

**Stock Movement UNION (simplified)**
```sql
SELECT 'sale' AS type, soi.product_variant_id, -soi.quantity AS qty_change, so.created_at, so.store_id
FROM sales_order_items soi JOIN sales_orders so ON ...
WHERE so.status = 'paid'
UNION ALL
SELECT 'adjustment', sa.product_variant_id, sa.quantity_change, sa.created_at, sa.store_id
FROM stock_adjustments sa
UNION ALL
SELECT 'reception', rop.product_variant_id, rop.quantity_received, ro.created_at, ro.store_id
FROM reception_order_product rop JOIN reception_orders ro ON ...
UNION ALL
SELECT 'transfer_out'/'transfer_in', sti.product_variant_id, ...
FROM stock_transfer_items sti JOIN stock_transfers st ON ...
```

> Implement as a raw DB UNION with pagination, or via a dedicated `StockMovementQuery` class using `DB::table()->union()`.

## Key Indexes (Verify / Add)
- `stock_batches(store_id, expiry_date, status)`
- `stock_adjustments(product_variant_id, store_id, created_at)`
- `sales_order_items(product_variant_id)`
- `reception_order_product(product_variant_id)`
