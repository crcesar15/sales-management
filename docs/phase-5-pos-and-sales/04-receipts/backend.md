# Backend — Receipts

## Implementation Steps
1. Confirm `token` UUID column exists on `sales_orders` (added in Task 03)
2. Create `ReceiptController` with `show()` (public) and `redirect()` (auth) methods
3. Register public route outside `auth` middleware group
4. In `show()`: load order by token, eager-load all relations needed for receipt
5. Load store settings (name, address, phone, logo) from `settings`
6. Load receipt customisation (`receipt_header`, `receipt_footer`) from `settings`
7. Return Inertia render with `ReceiptLayout` (minimal, no sidebar)

## Key Files to Create
```
app/Http/Controllers/ReceiptController.php
resources/js/Pages/Receipts/Show.vue
resources/js/Layouts/ReceiptLayout.vue
```

## Controller Pattern
```php
public function show(string $token): Response
{
    $order = SalesOrder::with([
        'customer', 'user', 'store',
        'items.productVariant.product',
        'items.saleUnit',
    ])->where('token', $token)->firstOrFail();

    abort_if($order->status !== 'paid', 404);

    return Inertia::render('Receipts/Show', [
        'order'          => new ReceiptOrderResource($order),
        'store'          => $this->storeSettings(),
        'receipt_header' => setting('receipt.receipt_header'),
        'receipt_footer' => setting('receipt.receipt_footer'),
    ]);
}
```

## Settings Retrieval Helper
```php
private function storeSettings(): array
{
    return [
        'name'     => setting('general.store_name'),
        'address'  => setting('general.store_address'),
        'phone'    => setting('general.store_phone'),
        'logo_url' => /* media library URL or settings path */,
    ];
}
```

## Gotchas
- Only `paid` orders should be publicly accessible — return 404 for non-paid
- Do NOT expose the sequential `id` in the public URL — use `token` only
- Eager load everything in a single query — receipt page must not have N+1
- Store logo: retrieve via Spatie Media Library `getFirstMediaUrl()` on Store model, or a settings path
- Product/variant names: if products can be deleted, snapshot names on `sales_order_items` or eager load with `withTrashed`
