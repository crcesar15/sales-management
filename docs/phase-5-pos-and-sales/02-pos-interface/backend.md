# Backend — POS Interface

## Implementation Steps
1. Create `PosController` with `index()`, `searchProducts()`, `checkout()`, `holdOrder()`, `heldOrders()`, `resumeOrder()` methods
2. Create `CheckoutRequest` form request with full validation including shift and payments
3. Create `CheckoutService` to encapsulate order creation, payment splitting, and FIFO deduction logic
4. Wrap entire checkout in `DB::transaction()` with pessimistic locking on batches
5. Calculate totals server-side (never trust client totals for financial records)
6. Auto-generate receipt `token` (UUID) on order creation via `SalesOrder::booted()` hook
7. Fire `OrderPaid` event after successful checkout (for activity log, hooks)
8. Validate `cash_register_shift_id` on checkout — shift must exist and be open
9. Validate split payments — sum of payment amounts must equal computed total

## Key Files to Create
```
app/Http/Controllers/PosController.php
app/Http/Requests/CheckoutRequest.php
app/Http/Requests/HoldOrderRequest.php
app/Services/CheckoutService.php
app/Services/FifoStockDeductionService.php
app/Exceptions/InsufficientStockException.php
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

## Split Payment Validation
```php
// In CheckoutRequest::withValidator()
$validator->after(function ($validator) {
    $paymentsTotal = collect($this->payments)->sum('amount');
    $computedTotal = $this->computeTotal(); // uses the same formula as above
    if (abs($paymentsTotal - $computedTotal) > 0.01) {
        $validator->errors()->add('payments', 'Payment total must equal order total.');
    }
});
```

## FIFO Deduction
```php
// FifoStockDeductionService::deductForOrder(SalesOrder $order)
// Called within an existing DB::transaction
foreach ($order->items as $item) {
    $needed = $item->quantity * $item->conversion_factor; // base units
    $batches = Batch::where('product_variant_id', $item->product_variant_id)
        ->where('store_id', $order->store_id)
        ->where('status', 'active')
        ->where('remaining_quantity', '>', 0)
        ->orderBy('created_at', 'asc')
        ->lockForUpdate()
        ->get();

    foreach ($batches as $batch) {
        $take = min($batch->remaining_quantity, $needed);
        $batch->decrement('remaining_quantity', $take);
        $batch->increment('sold_quantity', $take);
        $needed -= $take;
        if ($needed <= 0) break;
    }

    if ($needed > 0) {
        throw new InsufficientStockException($item->product_variant_id, $needed);
    }
}

// After all deductions
foreach ($affectedVariantIds as $variantId) {
    ProductVariant::find($variantId)?->recalculateStock();
}
```

## Shift Gate Validation
```php
// In PosController::checkout() and PosController::holdOrder()
$shift = CashRegisterShift::where('id', $request->cash_register_shift_id)
    ->where('status', 'open')
    ->firstOrFail();

// Verify shift belongs to the authenticated user
abort_if($shift->user_id !== auth()->id(), 422, 'Shift does not belong to the current user.');
```

## Hold/Resume Endpoints
```php
// PosController::holdOrder()
// Creates a sales_order with status='held', items stored, no stock deduction, no payments

// PosController::heldOrders()
// Returns held orders for the current shift: status='held' AND cash_register_shift_id matches

// PosController::resumeOrder(SalesOrder $order)
// Transitions status from 'held' to 'draft', returns order with items for cart population
```

## Product Search
- Single query joining `products`, `product_variants`, `product_variant_units` (where `type='sale'`)
- Search: `products.name LIKE`, `product_variants.sku LIKE`, `product_variants.barcode =`
- Barcode field: exact match attempt first, then LIKE fallback
- Only return `active` sale units
- Limit 15 products
- Include store-specific stock info from `batches` (sum of `remaining_quantity` where `status='active'`)

## CheckoutRequest Validation
| Field | Rules |
|---|---|
| `customer_id` | `nullable\|exists:customers,id` |
| `cash_register_shift_id` | `required\|exists:cash_register_shifts,id` |
| `discount_type` | `required\|in:flat,percentage` |
| `discount_value` | `required\|numeric\|min:0` |
| `notes` | `nullable\|string` |
| `items` | `required\|array\|min:1` |
| `items.*.product_variant_id` | `required\|exists:product_variants,id` |
| `items.*.sale_unit_id` | `nullable\|exists:product_variant_units,id` |
| `items.*.quantity` | `required\|integer\|min:1` |
| `items.*.unit_price` | `required\|numeric\|min:0` |
| `payments` | `required\|array\|min:1` |
| `payments.*.payment_method` | `required\|in:cash,credit_card,qr,transfer` |
| `payments.*.amount` | `required\|numeric\|min:0.01` |
| `payments.*.reference` | `nullable\|string\|max:255` |

Custom validation (in `withValidator()`):
- `cash_register_shift_id` must reference an open shift belonging to the authenticated user
- Sum of `payments.*.amount` must equal the computed order total (within 0.01 tolerance)

## Gotchas
- Always recalculate totals server-side; ignore client-sent totals for financial records
- Conversion factor must be snapshotted on `sales_order_items` at time of sale
- Check stock availability before deducting — throw `InsufficientStockException` if insufficient
- `sale_unit_id` is nullable — if null, use base unit (conversion_factor = 1)
- `inventory_batches` is `batches` in the codebase; `sale_units` is `product_variant_units` with `type='sale'`
- Split payments must sum to the total — any over/under payment is a validation error
- Held orders store items but do NOT deduct stock — stock is only deducted on checkout (transition to `paid`)
- The `payment_method` on `sales_orders` is informational/default; actual payment records are in `sales_order_payments`