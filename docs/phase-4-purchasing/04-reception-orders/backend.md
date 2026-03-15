# Task 04: Reception Orders — Backend

## Implementation Steps

1. **Migration** — `add_store_id_to_reception_orders_table`: add `store_id` FK to `stores`
2. **Models** — update `ReceptionOrder`; add `belongsTo` Store; update `ReceptionOrderProduct` with `expiry_date`
3. **Form Request** — `StoreReceptionOrderRequest`: validate PO exists and is in `approved` or `sent` status; validate `store_id`; validate items array
4. **Controller** — `ReceptionOrderController`; `complete()` action delegates to service
5. **ReceptionOrderService::complete()** — runs entirely in a DB transaction:
   - For each line item: fetch `conversion_factor` from `catalog` (vendor+variant)
   - Calculate `base_quantity = quantity × conversion_factor`
   - Increment stock on `product_variants` or `stock` table
   - Create a `Batch` record: `initial_quantity`, `store_id`, `expiry_date`
   - Log activity on the reception order
6. **Guard** — prevent completing an already-completed or cancelled reception
7. **Guard** — prevent creating a reception against a `draft`, `awaiting_approval`, or `paid` PO
8. **Routes** — `Route::apiResource('reception-orders', ...)` + `POST reception-orders/{ro}/complete`

## Key Classes / Files

| File                                                  | Purpose                          |
|-------------------------------------------------------|----------------------------------|
| `app/Models/ReceptionOrder.php`                       | Model with relations             |
| `app/Models/ReceptionOrderProduct.php`                | Line item with expiry_date       |
| `app/Services/ReceptionOrderService.php`              | Complete action + stock update   |
| `app/Http/Controllers/ReceptionOrderController.php`   | HTTP layer                       |

## Important Patterns

```php
// Stock update in transaction
DB::transaction(function () use ($reception) {
    foreach ($reception->items as $item) {
        $catalog = Catalog::where('vendor_id', $reception->vendor_id)
            ->where('product_variant_id', $item->product_variant_id)
            ->firstOrFail();
        $baseQty = $item->quantity * $catalog->conversion_factor;
        // increment stock ...
        Batch::create([
            'store_id'         => $reception->store_id,
            'initial_quantity' => $baseQty,
            'expiry_date'      => $item->expiry_date,
        ]);
    }
});
```

## Packages
- `spatie/laravel-activitylog` — log completion event
- `spatie/laravel-permission` — `reception_orders.manage`

## Gotchas
- `conversion_factor` must be looked up from `catalog` at completion time — not stored on reception
- If catalog entry has been deleted since PO creation, handle gracefully (default factor = 1 or throw)
- Cancelling a completed reception does NOT reverse stock — document this explicitly for users
