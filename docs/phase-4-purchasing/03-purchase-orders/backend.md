# Task 03: Purchase Orders — Backend

## Implementation Steps

1. **Migrations** — `create_purchase_orders_table` and `create_purchase_order_product_table`
2. **Models** — `PurchaseOrder` with status enum cast; `PurchaseOrderProduct` (line item)
3. **Status Machine** — `PurchaseOrderService::transition(PurchaseOrder $po, string $newStatus)` validates allowed transitions and throws on invalid
4. **Price Snapshot** — on PO create, resolve each line item's price from `Catalog::active()->where('vendor_id',...)->where('product_variant_id',...)` — abort if not found
5. **Total Calculation** — `PurchaseOrderService::recalculate(PurchaseOrder $po)` recomputes `sub_total`, applies `discount`, sets `total`
6. **Form Requests** — `StorePurchaseOrderRequest` validates vendor, items array, each item's variant exists in vendor's active catalog
7. **Controller** — `PurchaseOrderController` with individual action methods for each status transition
8. **Activity Log** — log status transitions: `activity()->on($po)->log("Status changed to {$newStatus}")`
9. **Authorization** — `purchase_orders.create` for create/edit/submit/cancel; `purchase_orders.approve` for approve/send/pay
10. **Cancellation Guard** — block cancel if status is `sent` or `paid`

## Key Classes / Files

| File                                                  | Purpose                        |
|-------------------------------------------------------|--------------------------------|
| `app/Models/PurchaseOrder.php`                        | Model with casts, relations    |
| `app/Models/PurchaseOrderProduct.php`                 | Line item model                |
| `app/Services/PurchaseOrderService.php`               | Business logic, transitions    |
| `app/Http/Controllers/PurchaseOrderController.php`    | HTTP layer                     |
| `app/Http/Requests/StorePurchaseOrderRequest.php`     | Validation                     |

## Important Patterns

```php
// Allowed transitions map
private const TRANSITIONS = [
    'draft'              => ['awaiting_approval', 'cancelled'],
    'awaiting_approval'  => ['approved', 'cancelled'],
    'approved'           => ['sent', 'cancelled'],
    'sent'               => ['paid'],
    'paid'               => [],
];

// Wrap create+line items in a transaction
DB::transaction(function () use ($data) { ... });
```

## Packages
- `spatie/laravel-activitylog` — status transition logging
- `spatie/laravel-permission` — dual-permission gating

## Gotchas
- Never trust price from the client; always fetch from catalog
- Discount is a flat amount — validate `discount <= sub_total`
- Activity log `causer` requires authenticated user in the service layer
