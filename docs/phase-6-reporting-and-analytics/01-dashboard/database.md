# Phase 6 — Task 01: Dashboard — Database

## No New Migrations Required
All data is aggregated from existing tables.

## Tables Consumed

| Table | Key Columns Used |
|---|---|
| `sales_orders` | `store_id`, `user_id`, `status`, `total`, `created_at` |
| `sales_order_items` | `sales_order_id`, `product_variant_id`, `quantity`, `subtotal` |
| `product_variants` | `id`, `product_id`, `stock_quantity`, `minimum_stock_level` |
| `products` | `id`, `name` |
| `purchase_orders` | `id`, `status`, `store_id` |
| `stock_alerts` | `id`, `type`, `status`, `store_id`, `created_at` |
| `stores` | `id`, `name` |
| `users` | `id`, `store_id`, `name` |

## Key Query Patterns

**Today's revenue**
```php
SalesOrder::where('status', 'paid')
    ->whereDate('created_at', today())
    ->when($storeId, fn($q) => $q->where('store_id', $storeId))
    ->sum('total');
```

**Monthly revenue**
```php
->whereYear('created_at', now()->year)
->whereMonth('created_at', now()->month)
->sum('total');
```

**Low stock count**
```php
ProductVariant::whereColumn('stock_quantity', '<', 'minimum_stock_level')
    ->when($storeId, fn($q) => $q->whereHas('store', ...))
    ->count();
```

**Top 5 products (current month)**
```php
SalesOrderItem::selectRaw('product_variant_id, SUM(quantity) as total_qty')
    ->whereHas('salesOrder', fn($q) => $q->where('status','paid')->currentMonth())
    ->groupBy('product_variant_id')
    ->orderByDesc('total_qty')
    ->limit(5)
    ->with('productVariant.product')
    ->get();
```

**Monthly revenue trend (last 6 months)**
```php
SalesOrder::selectRaw("DATE_FORMAT(created_at,'%Y-%m') as month, SUM(total) as revenue")
    ->where('status', 'paid')
    ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
    ->groupBy('month')
    ->orderBy('month')
    ->get();
```

## Key Indexes (Existing — Verify Present)
- `sales_orders(status, created_at, store_id)`
- `sales_order_items(sales_order_id, product_variant_id)`
- `product_variants(stock_quantity, minimum_stock_level)`
- `stock_alerts(status, store_id)`
