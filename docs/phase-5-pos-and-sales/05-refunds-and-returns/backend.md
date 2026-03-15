# Backend — Refunds & Returns

## Implementation Steps
1. Create migrations for `refunds` and `refund_items`
2. Create `Refund` and `RefundItem` Eloquent models
3. Create `RefundController` with `index`, `show`, `store`, `updateStatus`
4. Create `StoreRefundRequest` — validate qty against returnable amount
5. Create `RefundService` — encapsulates transitions and restocking logic
6. On `completed` transition: create new `inventory_batch` per returned variant
7. Log all status changes via Spatie Activity Log
8. Register `refunds.manage` permission

## Key Files to Create
```
app/Models/Refund.php
app/Models/RefundItem.php
app/Http/Controllers/RefundController.php
app/Http/Requests/StoreRefundRequest.php
app/Http/Requests/UpdateRefundStatusRequest.php
app/Services/RefundService.php
```

## Returnable Quantity Validation
```php
// StoreRefundRequest — custom validation rule per item
$alreadyReturned = RefundItem::whereHas('refund', fn($q) =>
    $q->whereIn('status', ['approved', 'completed'])
)->where('sales_order_item_id', $itemId)->sum('quantity_returned');

$returnable = $originalQty - $alreadyReturned;
Validator::validate(['qty' => $qty], ['qty' => "max:{$returnable}"]);
```

## Restock on Completion
```php
// RefundService::complete(Refund $refund)
DB::transaction(function () use ($refund) {
    foreach ($refund->items as $item) {
        $variant = $item->salesOrderItem->productVariant;
        InventoryBatch::create([
            'product_variant_id'  => $variant->id,
            'store_id'            => $refund->store_id,
            'quantity'            => $item->quantity_returned,
            'quantity_remaining'  => $item->quantity_returned,
            'note'                => "Returned via refund #{$refund->id}",
        ]);
    }
    $refund->update(['status' => 'completed']);
});
```

## Activity Log
```php
activity()->on($refund)
    ->causedBy(auth()->user())
    ->withProperties(['from' => $old, 'to' => $new])
    ->log('refund_status_changed');
```

## Status Transition Guard
```php
$allowed = match($refund->status) {
    'pending'  => ['approved', 'rejected'],
    'approved' => ['completed', 'rejected'],
    default    => [],
};
abort_unless(in_array($to, $allowed), 422, 'Invalid refund status transition.');
```

## Gotchas
- Restocking happens on `completed` only — not on `approved`
- `total_refund` is calculated and stored on creation, not re-derived
- Validate that all `sales_order_item_id` values belong to the target `sales_order`
- Store ID on refund must match the sales order's store ID
