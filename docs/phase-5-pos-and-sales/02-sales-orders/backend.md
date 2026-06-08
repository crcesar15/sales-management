# Backend — Sales Orders

## Implementation Steps
1. ~~Create migrations: `alter_sales_orders_table_add_columns`, `create_sales_order_items_table`, `create_sales_order_payments_table`~~ ✅ Done
2. ~~Create enums: `SalesOrderStatus`, `DiscountType`; update `PaymentMethod` enum to `{cash, credit_card, qr, transfer}`~~ ✅ Done
3. ~~Update `SalesOrder` model — add `$fillable`, `$casts`, relationships, `LogsActivity`, token auto-generation~~ ✅ Done (updated: added `booted()` UUID hook, `scopeHeld`, `scopeNotHeld`, `scopeStatus`; `payment_method` NOT on order header)
4. ~~Create `SalesOrderItem` model with `$fillable`, `$casts`, relationships~~ ✅ Done
5. ~~Create `SalesOrderPayment` model with `$fillable`, `$casts`, relationships~~ ✅ Done
6. Create `SalesOrderService` — create, update, status transitions, totals calculation, hold/resume, cancel, list with visibility scope ✅
7. Create `FifoStockDeductionService` — FIFO batch selection and deduction with pessimistic locking ✅
8. ~~Create `InsufficientStockException` custom exception~~ ❌ Not needed — project uses `InvalidArgumentException` consistently for business rule violations
9. ~~Create `SalesOrderController` (web)~~ ✅ Done
10. ~~Create form requests: `StoreSalesOrderRequest`, `UpdateSalesOrderRequest`, `TransitionStatusRequest`~~ ✅ Done
11. ~~Create API resources: `SalesOrderResource`, `SalesOrderCollection`, `SalesOrderItemResource`, `SalesOrderPaymentResource`~~ ✅ Done
12. ~~Register `sales.view`, `sales.view_all`, `sales.create`, `sales.manage` permissions~~ ✅ Already existed
13. ~~Add `salesOrders()` HasMany relationship to `CashRegisterShift` model~~ ✅ Done
14. ~~Add `cashRegisterShift()` BelongsTo relationship to `SalesOrder` model~~ ✅ Done (named `cashRegisterShift()`, not `shift()`)
15. ~~Update `CashRegisterShiftService::closeShift()` to include cash sales from `sales_order_payments` in `expected_closing` calculation~~ ✅ Done
16. ~~Register web routes for sales orders~~ ✅ Done
17. ~~Update sidebar menu entry for Sales Orders~~ ✅ Done

> **Note**: `FifoStockDeductionService` is defined in this task and also used by Task 03 (POS Interface) via `SalesOrderService::transitionStatus()` when an order transitions to `paid`. `SalesOrderService::create()` is used by both manual orders (this task's controllers) and POS checkout (Task 03's `PosController`).

> **Note**: API controller and API routes (`Api\SalesOrderController`) are deferred to a future task. This implementation covers the Inertia (web) side only.

> **Note**: `payment_method` is NOT a column on `sales_orders`. Split payments live exclusively in `sales_order_payments`. The `PaymentMethod` enum (`{cash, credit_card, qr, transfer}`) is only used on the `sales_order_payments` table. If a default/primary payment method is needed for display purposes, it should be derived from the first payment record.

## Key Files Created/Modified
```
app/Models/SalesOrder.php                          (updated — booted UUID, scopes)
app/Models/SalesOrderItem.php                      (existing)
app/Models/SalesOrderPayment.php                   (existing)
app/Services/SalesOrderService.php                 (new)
app/Services/FifoStockDeductionService.php         (new)
app/Http/Controllers/SalesOrderController.php      (new)
app/Http/Requests/SalesOrders/StoreSalesOrderRequest.php       (new)
app/Http/Requests/SalesOrders/UpdateSalesOrderRequest.php      (new)
app/Http/Requests/SalesOrders/TransitionStatusRequest.php       (new)
app/Http/Resources/SalesOrder/SalesOrderResource.php           (new)
app/Http/Resources/SalesOrder/SalesOrderCollection.php         (new)
app/Http/Resources/SalesOrderItem/SalesOrderItemResource.php   (new)
app/Http/Resources/SalesOrderPayment/SalesOrderPaymentResource.php (new)
app/Services/CashRegisterShiftService.php          (updated — cash sales in expected_closing)
routes/web.php                                     (updated — sales-orders routes)
resources/js/Layouts/Composables/useMenuItems.ts   (updated — sales-orders entry)
```

## SalesOrder Model (Updated)
```php
final class SalesOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'customer_id', 'user_id', 'store_id', 'status',
        'discount_type', 'discount_value',
        'sub_total', 'discount', 'tax_amount', 'total',
        'notes', 'token', 'cash_register_shift_id',
    ];

    // NOTE: payment_method is NOT on sales_orders — it lives in sales_order_payments only

    protected $casts = [
        'status' => SalesOrderStatus::class,
        'discount_type' => DiscountType::class,
        'discount_value' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
    ];

    protected static function booted(): void
    {
        static::creating(function (SalesOrder $order) {
            $order->token = (string) Str::uuid();
        });
    }

    // Relationships
    public function customer(): BelongsTo { ... }
    public function user(): BelongsTo { ... }
    public function store(): BelongsTo { ... }
    public function cashRegisterShift(): BelongsTo { ... }  // Note: named cashRegisterShift(), not shift()
    public function items(): HasMany { ... }
    public function payments(): HasMany { ... }

    // Scopes
    public function scopeHeld(Builder $query): void { ... }
    public function scopeNotHeld(Builder $query): void { ... }
    public function scopeStatus(Builder $query, string $status): void { ... }
}
```

## Status Transition Map
```
draft   → [sent, paid, held, cancelled]
held    → [draft, cancelled]
sent    → [paid, cancelled]
paid    → [cancelled]
```

- POS checkout: creates with `status = paid` directly
- POS hold: creates with `status = held`
- Manual: creates with `status = draft`, transitions through `sent → paid`
- Cancellation is a status change only — no automatic stock reversal (refund flow handles that)

## SalesOrderService Key Methods

```php
final class SalesOrderService
{
    public function create(array $data, User $actor): SalesOrder
    // Creates order with items and payments
    // Calculates totals server-side from items
    // Sets store_id from actor's store
    // Accepts optional `cash_register_shift_id` for POS orders
    // If status = 'paid', triggers FIFO deduction via FifoStockDeductionService
    // Wraps in DB::transaction
    // Used by: this task's SalesOrderController AND Task 03's PosController/CheckoutService

    public function update(SalesOrder $order, array $data, User $actor): SalesOrder
    // Updates a draft order (items, payments, totals recalculated)
    // Replaces items and payments (delete + recreate)
    // Throws InvalidArgumentException if order is not draft
    // Wraps in DB::transaction

    public function transitionStatus(SalesOrder $order, string $newStatus, User $actor): SalesOrder
    // Validates allowed transitions per the map above
    // If transitioning TO 'paid', calls FifoStockDeductionService::deductForOrder()
    // Logs status change with 'from' and 'to' properties

    public function holdOrder(array $data, User $actor): SalesOrder
    // Creates order with status='held', items stored, no stock deduction

    public function resumeOrder(SalesOrder $order, User $actor): SalesOrder
    // Transitions from 'held' to 'draft'
    // Returns order with items loaded for POS cart population

    public function calculateTotals(array $items, string $discountType, float $discountValue, float $taxRate): array
    // Returns [sub_total, discount, tax_amount, total]
    // Pure function — no DB side effects
    // Tax rate retrieved via Setting::get('tax_rate')

    public function cancel(SalesOrder $order, ?string $reason, User $actor): void
    // Guard: cannot cancel already cancelled orders
    // Set status to cancelled (no stock reversal)
    // Activity log with reason

    public function list(array $filters, User $actor, int $perPage = 20): LengthAwarePaginator
    // Scoped by store_id from actor's store
    // If actor lacks sales.view_all, filter to user_id = actor->id
    // Supports search (customer name, order ID), status, from, to filters
}
```

## FifoStockDeductionService

```php
final class FifoStockDeductionService
{
    public function deductForOrder(SalesOrder $order): void
    // Called within an existing DB::transaction
    // For each SalesOrderItem:
    //   1. Calculate base quantity needed = item->quantity * item->conversion_factor
    //   2. Select batches with pessimistic lock (lockForUpdate)
    //   3. Deduct from oldest batch first (FIFO)
    //   4. If total available < needed, throw InvalidArgumentException
    // After all deductions, call ProductVariant::recalculateStock() for each affected variant
}
```

> **Note**: Stock errors throw `InvalidArgumentException` with descriptive messages (e.g., "Insufficient stock for variant SKU: requested X, available Y"). This is consistent with the project's convention — no custom exception class is used.

## Visibility Scope
```php
// SalesOrderController::index() / SalesOrderService::list()
$query = SalesOrder::query()->where('store_id', $actor->stores()->first()->id);
if (! $actor->can('sales.view_all')) {
    $query->where('user_id', $actor->id);
}
```

## Gotchas
- FIFO deduction only fires when transitioning **to** `paid` — guard against double deduction
- `conversion_factor` must be snapshotted on item insert, not re-queried later
- Store scope must be applied on every query — never show cross-store orders
- Soft-delete is not used; `cancelled` is the terminal state
- `payment_method` is NOT on `sales_orders` — split payments are exclusively in `sales_order_payments`; derive a display value from the first payment if needed
- Held orders store items but do NOT deduct stock — stock is deducted on checkout (transition to `paid`)
- The `token` field is auto-generated via `SalesOrder::booted()` `creating` hook — do not set it manually
- Table name clarification: `inventory_batches` in earlier docs refers to `batches`; `sale_units` refers to `product_variant_units` with `type='sale'`
- Tax rate is retrieved via `Setting::get('tax_rate')` which returns a float (e.g., 16.0 for 16%)