# Phase 6 — Task 04: Purchasing Reports — Database

## No New Migrations Required

## Tables Consumed

| Table | Key Columns | Section |
|---|---|---|
| `purchase_orders` | `id`, `vendor_id`, `store_id`, `status`, `total`, `created_at`, `received_at` | PO Summary, Vendor Spend |
| `purchase_order_items` | `id`, `purchase_order_id`, `product_variant_id`, `quantity_ordered`, `unit_cost` | PO Summary, Accuracy |
| `vendors` | `id`, `name` | All sections |
| `reception_orders` | `id`, `purchase_order_id`, `store_id`, `created_at` | Reception Accuracy |
| `reception_order_product` | `id`, `reception_order_id`, `purchase_order_item_id`, `product_variant_id`, `quantity_received` | Accuracy |
| `product_variants` | `id`, `sku`, `product_id` | Accuracy display |
| `products` | `id`, `name` | Accuracy display |

## Key Query Patterns

**PO Summary — metrics**
```php
// Total spend: sum of completed POs
PurchaseOrder::whereIn('status', ['received', 'partially_received'])
    ->when($vendorId, fn($q) => $q->where('vendor_id', $vendorId))
    ->whereBetween('created_at', [$from, $to])
    ->sum('total');

// Average lead time (days)
PurchaseOrder::whereNotNull('received_at')
    ->selectRaw('AVG(DATEDIFF(received_at, created_at)) as avg_lead_time')
    ->first()?->avg_lead_time;
```

**Vendor Spend aggregation**
```php
PurchaseOrder::selectRaw('
    vendor_id,
    COUNT(*)        as order_count,
    SUM(total)      as total_ordered_value
')
->whereBetween('created_at', [$from, $to])
->groupBy('vendor_id')
->with('vendor:id,name')
->orderByDesc('total_ordered_value')
->get();
```

**Reception Accuracy — per line item**
```php
// Join purchase_order_items with reception_order_product
DB::table('purchase_order_items as poi')
    ->join('purchase_orders as po',      'po.id',   '=', 'poi.purchase_order_id')
    ->join('reception_order_product as rop', 'rop.purchase_order_item_id', '=', 'poi.id')
    ->join('reception_orders as ro',     'ro.id',   '=', 'rop.reception_order_id')
    ->selectRaw('
        po.id            as po_id,
        poi.quantity_ordered,
        rop.quantity_received,
        (rop.quantity_received - poi.quantity_ordered) as variance
    ')
    ->when($varianceType === 'over',  fn($q) => $q->whereRaw('rop.quantity_received > poi.quantity_ordered'))
    ->when($varianceType === 'under', fn($q) => $q->whereRaw('rop.quantity_received < poi.quantity_ordered'))
    ->paginate(25);
```

## Key Indexes (Verify / Add)
- `purchase_orders(vendor_id, status, created_at)`
- `purchase_orders(status, received_at)` — for lead time calculation
- `reception_order_product(purchase_order_item_id)`
