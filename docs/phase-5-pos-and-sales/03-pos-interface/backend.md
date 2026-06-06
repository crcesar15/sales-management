# Backend — POS Interface

## Implementation Steps
1. Extend `PosController` with `searchProducts()`, `checkout()`, `holdOrder()`, `heldOrders()`, `resumeOrder()` methods (index already exists)
2. Create `CheckoutRequest` form request with full validation including shift and payments
3. Create `CheckoutService` as a thin coordinator that validates the shift gate and delegates to `SalesOrderService`
4. Calculate totals server-side (never trust client totals for financial records) — delegates to `SalesOrderService::calculateTotals()`
5. Auto-generate receipt `token` (UUID) on order creation via `SalesOrder::booted()` hook (from Task 02)
6. Validate `cash_register_shift_id` on checkout — shift must exist and be open
7. Validate split payments — sum of payment amounts must equal computed total

> **Note**: `FifoStockDeductionService`, `InsufficientStockException`, and `SalesOrderService` are defined in Task 02 (Sales Orders). `PosController::checkout()` delegates to `SalesOrderService::create()` with status = paid. `PosController::holdOrder()` delegates to `SalesOrderService::holdOrder()`. `PosController::resumeOrder()` delegates to `SalesOrderService::resumeOrder()`.

## Key Files to Create
```
app/Http/Controllers/Pos/PosController.php (extend existing)
app/Http/Requests/CheckoutRequest.php (new)
app/Http/Requests/HoldOrderRequest.php (new)
app/Services/CheckoutService.php (new)
```

> `FifoStockDeductionService` and `InsufficientStockException` are defined in Task 02 (Sales Orders), not duplicated here.

## CheckoutService (Thin Coordinator)
```php
final class CheckoutService
{
    public function __construct(
        private readonly SalesOrderService $salesOrderService,
    ) {}

    public function checkout(array $data, User $actor): SalesOrder
    {
        // 1. Validate shift gate (open shift, belongs to authenticated user)
        // 2. Build order data structure from cart items, discount, tax, payments
        // 3. Delegate to SalesOrderService::create() with status = 'paid'
        // 4. Return the created order
    }

    public function holdOrder(array $data, User $actor): SalesOrder
    {
        // Delegate to SalesOrderService::holdOrder()
    }

    public function resumeOrder(SalesOrder $order, User $actor): SalesOrder
    {
        // Delegate to SalesOrderService::resumeOrder()
    }
}
```

## Total Calculation
Totals are calculated server-side via `SalesOrderService::calculateTotals()` (from Task 02). `CheckoutService` does not duplicate this logic — it calls the service method with the cart data.

## Split Payment Validation
```php
// In CheckoutRequest::withValidator()
$validator->after(function ($validator) {
    $paymentsTotal = collect($this->payments)->sum('amount');
    $computedTotal = $this->computeTotal(); // uses the same formula as SalesOrderService::calculateTotals()
    if (abs($paymentsTotal - $computedTotal) > 0.01) {
        $validator->errors()->add('payments', 'Payment total must equal order total.');
    }
});
```

## Shift Gate Validation
```php
// In CheckoutService::checkout() and CheckoutService::holdOrder()
$shift = CashRegisterShift::where('id', $request->cash_register_shift_id)
    ->where('status', 'open')
    ->firstOrFail();

// Verify shift belongs to the authenticated user
abort_if($shift->user_id !== auth()->id(), 422, 'Shift does not belong to the current user.');
```

## Hold/Resume Endpoints
```php
// PosController::holdOrder()
// Calls CheckoutService::holdOrder() → SalesOrderService::holdOrder()
// Creates a sales_order with status='held', items stored, no stock deduction, no payments

// PosController::heldOrders()
// Returns held orders for the current shift: status='held' AND cash_register_shift_id matches

// PosController::resumeOrder(SalesOrder $order)
// Calls CheckoutService::resumeOrder() → SalesOrderService::resumeOrder()
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
- Always recalculate totals server-side via `SalesOrderService::calculateTotals()`; ignore client-sent totals for financial records
- Conversion factor must be snapshotted on `sales_order_items` at time of sale — handled by `SalesOrderService::create()`
- `InsufficientStockException` is thrown by `FifoStockDeductionService` (from Task 02) — catch it in `PosController` and return 422
- `sale_unit_id` is nullable — if null, use base unit (conversion_factor = 1)
- `inventory_batches` is `batches` in the codebase; `sale_units` is `product_variant_units` with `type='sale'`
- Split payments must sum to the total — any over/under payment is a validation error
- Held orders store items but do NOT deduct stock — stock is only deducted on checkout (transition to `paid`) via `SalesOrderService::transitionStatus()`
- The `payment_method` on `sales_orders` is informational/default; actual payment records are in `sales_order_payments`