# Backend — Sales Orders

## Implementation Steps
1. Create migrations for `sales_orders`, `sales_order_items`, add `store_id` and `tax_amount`
2. Create `SalesOrder` and `SalesOrderItem` Eloquent models with relationships
3. Auto-generate `token` (UUID) in `SalesOrder::booted()` `creating` hook
4. Create `SalesOrderController` (index, show, store, update, updateStatus)
5. Create `SalesOrderService` — wraps status transitions and stock deduction
6. Enforce visibility scope in `index()`: `view_all` vs own orders
7. Log status changes via Spatie Activity Log
8. Register `sales.view`, `sales.view_all`, `sales.manage`, `sales.create` permissions

## Key Files to Create
```
app/Models/SalesOrder.php
app/Models/SalesOrderItem.php
app/Http/Controllers/SalesOrderController.php
app/Http/Requests/StoreSalesOrderRequest.php
app/Http/Requests/UpdateSalesOrderStatusRequest.php
app/Services/SalesOrderService.php
```

## Token Auto-Generation
```php
protected static function booted(): void
{
    static::creating(function (SalesOrder $order) {
        $order->token = (string) Str::uuid();
    });
}
```

## Visibility Scope
```php
// SalesOrderController::index()
$query = SalesOrder::query()->where('store_id', auth()->user()->store_id);
if (! auth()->user()->can('sales.view_all')) {
    $query->where('user_id', auth()->id());
}
```

## Status Transition Guard
```php
// SalesOrderService::transition(SalesOrder $order, string $to)
$allowed = match($order->status) {
    'draft'  => ['sent', 'paid', 'cancelled'],
    'sent'   => ['paid', 'cancelled'],
    'paid'   => ['cancelled'],
    default  => [],
};
abort_unless(in_array($to, $allowed), 422, 'Invalid status transition.');
```

## Activity Log
```php
activity()->on($order)
    ->withProperties(['from' => $old, 'to' => $new])
    ->log('status_changed');
```

## Gotchas
- FIFO deduction only fires when transitioning **to** `paid` — guard against double deduction
- `conversion_factor` must be snapshotted on item insert, not re-queried later
- Store scope must be applied on every query — never show cross-store orders
- Soft-delete is not used; `cancelled` status is the terminal state
