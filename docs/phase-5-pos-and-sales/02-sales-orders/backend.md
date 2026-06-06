# Backend — Sales Orders

## Implementation Steps
1. Create migrations: `alter_sales_orders_table_add_columns`, `create_sales_order_items_table`, `create_sales_order_payments_table`
2. Create enums: `SalesOrderStatus`, `DiscountType`; update `PaymentMethod` enum to `{cash, credit_card, qr, transfer}`
3. Update `SalesOrder` model — add `$fillable`, `$casts`, relationships, `LogsActivity`, token auto-generation
4. Create `SalesOrderItem` model with `$fillable`, `$casts`, relationships
5. Create `SalesOrderPayment` model with `$fillable`, `$casts`, relationships
6. Create `SalesOrderService` — status transitions, totals calculation, hold/resume, FIFO integration
7. Create `FifoStockDeductionService` — FIFO batch selection and deduction with pessimistic locking
8. Create `InsufficientStockException` custom exception
9. Create `SalesOrderController` (web) and `Api\SalesOrderController` (API)
10. Create form requests: `StoreSalesOrderRequest`, `UpdateSalesOrderRequest`, `TransitionStatusRequest`
11. Create API resources: `SalesOrderResource`, `SalesOrderCollection`, `SalesOrderItemResource`, `SalesOrderPaymentResource`
12. Register `sales.view`, `sales.view_all`, `sales.create`, `sales.manage` permissions
13. Add `salesOrders()` HasMany relationship to `CashRegisterShift` model
14. Add `shift()` BelongsTo relationship to `SalesOrder` model (linking to `CashRegisterShift` via `cash_register_shift_id`)
15. Update `CashRegisterShiftService::closeShift()` to include cash sales from `sales_order_payments` in `expected_closing` calculation

> **Note**: `FifoStockDeductionService` and `InsufficientStockException` are defined in this task and also used by Task 03 (POS Interface) via `SalesOrderService::transitionStatus()` when an order transitions to `paid`. `SalesOrderService::create()` is used by both manual orders (this task's controllers) and POS checkout (Task 03's `PosController`).

## Key Files to Create/Modify
```
app/Models/SalesOrder.php           (major rewrite — add fillable, casts, relationships)
app/Models/SalesOrderItem.php       (new)
app/Models/SalesOrderPayment.php    (new)
app/Services/SalesOrderService.php  (new)
app/Services/FifoStockDeductionService.php (new)
app/Exceptions/InsufficientStockException.php (new)
app/Http/Controllers/SalesOrderController.php (new)
app/Http/Controllers/Api/SalesOrderController.php (new)
app/Http/Requests/SalesOrders/StoreSalesOrderRequest.php (new)
app/Http/Requests/SalesOrders/UpdateSalesOrderRequest.php (new)
app/Http/Requests/SalesOrders/TransitionStatusRequest.php (new)
app/Http/Resources/SalesOrder/SalesOrderResource.php (new)
app/Http/Resources/SalesOrder/SalesOrderCollection.php (new)
app/Http/Resources/SalesOrderItem/SalesOrderItemResource.php (new)
app/Http/Resources/SalesOrderPayment/SalesOrderPaymentResource.php (new)
database/migrations/xxxx_alter_sales_orders_table_add_columns.php (new)
database/migrations/xxxx_create_sales_order_items_table.php (new)
database/migrations/xxxx_create_sales_order_payments_table.php (new)
app/Enums/PaymentMethod.php         (update values)
app/Enums/SalesOrderStatus.php       (new)
app/Enums/DiscountType.php           (new)
```

## SalesOrder Model (Rewrite)
```php
final class SalesOrder extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'customer_id', 'user_id', 'store_id', 'status',
        'payment_method', 'discount_type', 'discount_value',
        'sub_total', 'discount', 'tax_amount', 'total',
        'notes', 'token', 'cash_register_shift_id',
    ];

    protected $casts = [
        'status' => SalesOrderStatus::class,
        'payment_method' => PaymentMethod::class,
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
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function store(): BelongsTo { return $this->belongsTo(Store::class); }
    public function shift(): BelongsTo { return $this->belongsTo(CashRegisterShift::class, 'cash_register_shift_id'); }
    public function items(): HasMany { return $this->hasMany(SalesOrderItem::class); }
    public function payments(): HasMany { return $this->hasMany(SalesOrderPayment::class); }

    // Scopes
    public function scopeHeld(Builder $query): void { $query->where('status', SalesOrderStatus::HELD); }
    public function scopeNotHeld(Builder $query): void { $query->where('status', '!=', SalesOrderStatus::HELD->value); }
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

    public function cancel(SalesOrder $order, ?string $reason, User $actor): void
    // Guard: cannot cancel already cancelled orders
    // Set status to cancelled (no stock reversal)
    // Activity log with reason
}
```

## FifoStockDeductionService

> **Note**: This service is defined in this task and also used by Task 03 (POS Interface). The POS checkout calls `SalesOrderService::create()` with `status = 'paid'`, which internally calls `FifoStockDeductionService::deductForOrder()`.

```php
final class FifoStockDeductionService
{
    public function deductForOrder(SalesOrder $order): void
    // Called within an existing DB::transaction
    // For each SalesOrderItem:
    //   1. Calculate base quantity needed = item->quantity * item->conversion_factor
    //   2. Select batches with pessimistic lock:
    //      Batch::where('product_variant_id', $item->product_variant_id)
    //        ->where('store_id', $order->store_id)
    //        ->where('status', 'active')
    //        ->where('remaining_quantity', '>', 0)
    //        ->orderBy('created_at', 'asc')
    //        ->lockForUpdate()
    //        ->get()
    //   3. Deduct from oldest batch first
    //   4. If total available < needed, throw InsufficientStockException
    // After all deductions, call ProductVariant::recalculateStock() for each affected variant
}
```

## Visibility Scope
```php
// SalesOrderController::index()
$query = SalesOrder::query()->where('store_id', $actor->store_id);
if (! $actor->can('sales.view_all')) {
    $query->where('user_id', $actor->id);
}
```

## PaymentMethod Enum Update
The existing `PaymentMethod` enum needs to be updated from `{bank_transfer, cash, check, credit_card}` to `{cash, credit_card, qr, transfer}` to match the database and POS requirements. This affects:
- `sales_orders.payment_method` column
- `sales_order_payments.payment_method` column
- Any code referencing `PaymentMethod` enum values

## Gotchas
- FIFO deduction only fires when transitioning **to** `paid` — guard against double deduction
- `conversion_factor` must be snapshotted on item insert, not re-queried later
- Store scope must be applied on every query — never show cross-store orders
- Soft-delete is not used; `cancelled` is the terminal state
- `payment_method` on `sales_orders` is informational/default; actual payment breakdown is in `sales_order_payments`
- Held orders store items but do NOT deduct stock — stock is deducted on checkout (transition to `paid`)
- The `token` field is auto-generated via `SalesOrder::booted()` `creating` hook — do not set it manually
- Table name clarification: `inventory_batches` in earlier docs refers to `batches`; `sale_units` refers to `product_variant_units` with `type='sale'`