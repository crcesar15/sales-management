# API — Receipts

## Endpoints

| Method | Path | Description | Auth |
|---|---|---|---|
| `GET` | `/receipts/{token}` | Public receipt view | None (public) |
| `GET` | `/sales-orders/{order}/receipt` | Redirect to public receipt | `sales.view` |

## Route Definition
```php
// Public — outside auth middleware group
Route::get('/receipts/{token}', [ReceiptController::class, 'show'])->name('receipts.show');

// Authenticated shortcut
Route::get('/sales-orders/{order}/receipt', [ReceiptController::class, 'redirect'])
    ->middleware('auth')
    ->name('sales-orders.receipt');
```

## Receipt Page Props
```json
{
  "order": {
    "id": 88,
    "created_at": "2026-03-15 10:30:00",
    "payment_method": "cash",
    "cashier": "Jane Smith",
    "customer": "John Doe",
    "items": [
      {
        "product_name": "Apple Juice",
        "variant_sku": "AJ-500ML",
        "sale_unit_name": "Bottle",
        "quantity": 3,
        "unit_price": 25.00,
        "line_total": 75.00
      }
    ],
    "sub_total": 75.00,
    "discount": 7.50,
    "tax_amount": 4.73,
    "total": 72.23
  },
  "store": {
    "name": "My Store",
    "address": "123 Main St",
    "phone": "02-000-0000",
    "logo_url": "/storage/logo.png"
  },
  "receipt_header": "Thank you for shopping with us!",
  "receipt_footer": "No returns after 7 days."
}
```

## Notes
- No JSON API — receipt is an Inertia page rendered server-side
- Invalid `token` → controller returns `abort(404)`
- No rate limiting required (low traffic, no sensitive data)
