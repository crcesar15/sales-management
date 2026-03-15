# Phase 6 — Task 02: Sales Reports — Database

## No New Migrations Required

## Tables Consumed

| Table | Key Columns |
|---|---|
| `sales_orders` | `id`, `store_id`, `user_id`, `customer_id`, `status`, `total`, `discount_total`, `tax_total`, `payment_method`, `created_at` |
| `sales_order_items` | `sales_order_id`, `product_variant_id`, `quantity`, `unit_price`, `subtotal` |
| `customers` | `id`, `name` |
| `users` | `id`, `name`, `store_id` |
| `product_variants` | `id`, `product_id`, `sku` |
| `products` | `id`, `name` |
| `stores` | `id`, `name` |

## Key Query Patterns

**Aggregate metrics with filters**
```php
$base = SalesOrder::query()
    ->when($storeId,   fn($q) => $q->where('store_id',       $storeId))
    ->when($userId,    fn($q) => $q->where('user_id',         $userId))
    ->when($status,    fn($q) => $q->where('status',          $status))
    ->when($method,    fn($q) => $q->where('payment_method',  $method))
    ->whereBetween('created_at', [$dateFrom, $dateTo]);

$metrics = $base->selectRaw('
    COUNT(*)             as order_count,
    SUM(total)           as total_revenue,
    SUM(discount_total)  as total_discount,
    SUM(tax_total)       as total_tax
')->first();
```

**Refund total (separate)**
```php
$refundTotal = (clone $base)->where('status', 'refunded')->sum('total');
```

**Filter by product/variant (subquery)**
```php
->when($variantId, fn($q) => $q->whereHas(
    'items', fn($i) => $i->where('product_variant_id', $variantId)
))
```

**Breakdown table with relations**
```php
SalesOrder::with(['customer:id,name', 'user:id,name'])
    ->withCount('items')
    ->paginate(25);
```

## Key Indexes (Verify / Add If Missing)
- `sales_orders(user_id, created_at)`
- `sales_orders(store_id, status, created_at)`
- `sales_orders(payment_method, created_at)`
- `sales_order_items(product_variant_id)`

## Recommended Additional Index
```php
// Migration if not present
$table->index(['store_id', 'status', 'created_at'], 'so_store_status_date');
```
