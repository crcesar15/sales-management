# Backend — POS Interface

## Implementation Steps
1. Create `PosController` with `index()`, `searchProducts()`, `checkout()` methods
2. Create `CheckoutRequest` form request with full validation
3. Create `CheckoutService` to encapsulate order creation and FIFO deduction logic
4. Wrap entire checkout in `DB::transaction()` with pessimistic locking on batches
5. Calculate totals server-side (never trust client totals for financial records)
6. Auto-generate receipt `token` (UUID) on order creation
7. Fire `OrderPaid` event after successful checkout (for activity log, hooks)

## Key Files to Create
```
app/Http/Controllers/PosController.php
app/Http/Requests/CheckoutRequest.php
app/Services/CheckoutService.php
app/Services/FifoStockDeductionService.php
```

## Total Calculation (Server-Side)
```php
$subtotal = collect($items)->sum(fn($i) => $i['unit_price'] * $i['quantity']);
$discount = $discount_type === 'percentage'
    ? $subtotal * ($discount_value / 100)
    : $discount_value;
$taxable  = max(0, $subtotal - $discount);
$tax      = $taxable * $taxRate;
$total    = $taxable + $tax;
```

## FIFO Deduction
```php
// FifoStockDeductionService::deduct(int $variantId, int $qty, int $conversionFactor)
$needed = $qty * $conversionFactor; // base units to deduct
foreach ($batches as $batch) {
    $take = min($batch->quantity_remaining, $needed);
    $batch->decrement('quantity_remaining', $take);
    $needed -= $take;
    if ($needed <= 0) break;
}
if ($needed > 0) throw new InsufficientStockException(...);
```

## Product Search
- Single query joining `products`, `product_variants`, `sale_units`
- Search: `products.name LIKE`, `product_variants.sku LIKE`, `product_variants.barcode =`
- Barcode field: exact match attempt first, then LIKE fallback
- Limit 15 products

## Gotchas
- Always recalculate totals server-side; ignore client-sent totals
- Conversion factor must be snapshotted on `sales_order_items` at time of sale
- Check stock availability before deducting — throw if insufficient
- `sale_unit_id` is nullable — if null, use base unit (conversion_factor = 1)
