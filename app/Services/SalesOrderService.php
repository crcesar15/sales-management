<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CashRegisterShiftStatus;
use App\Enums\DiscountType;
use App\Enums\PaymentMethod;
use App\Enums\SalesOrderPaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Models\Batch;
use App\Models\CashRegisterShift;
use App\Models\CustomerReceivableEntry;
use App\Models\MeasurementUnit;
use App\Models\ProductVariant;
use App\Models\ProductVariantUnit;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderPayment;
use App\Models\SalesOrderStockAllocation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class SalesOrderService
{
    public function __construct(
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, SalesOrder>
     */
    public function list(array $filters, User $actor, int $perPage = 20): LengthAwarePaginator
    {
        $query = SalesOrder::query()
            ->with([
                'customer',
                'user',
                'store',
                'cashRegisterShift',
                'items.productVariant.product.brand',
                'items.saleUnit',
                'payments',
            ])
            ->where('store_id', $actor->stores()->first()->id ?? 0);

        if (! $actor->can('sales.view_all')) {
            $query->where('user_id', $actor->id);
        }

        $query->when(
            $filters['search'] ?? null,
            fn ($q, $search) => $q->where(function ($q) use ($search): void {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            }),
        );

        $query->when(
            $filters['status'] ?? null,
            fn ($q, $status) => $q->where('status', $status),
        );

        $query->when(
            $filters['from'] ?? null,
            fn ($q, $from) => $q->whereDate('created_at', '>=', $from),
        );

        $query->when(
            $filters['to'] ?? null,
            fn ($q, $to) => $q->whereDate('created_at', '<=', $to),
        );

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): SalesOrder
    {
        return DB::transaction(function () use ($data, $actor): SalesOrder {
            $items = $data['items'] ?? [];
            $discountType = $data['discount_type'] ?? DiscountType::FLAT->value;
            $discountValue = (float) ($data['discount_value'] ?? 0);
            $taxRate = (float) Setting::get('tax_rate', 0);

            $totals = $this->calculateTotals($items, $discountType, $discountValue, $taxRate);

            $order = SalesOrder::create([
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $actor->id,
                'store_id' => $data['store_id'],
                'cash_register_shift_id' => $data['cash_register_shift_id'] ?? null,
                'status' => SalesOrderStatus::DRAFT,
                'payment_status' => SalesOrderPaymentStatus::PENDING,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'sub_total' => $totals['sub_total'],
                'discount' => $totals['discount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $conversionFactor = $this->conversionFactorFor($item);
                $lineTotal = (float) $item['unit_price'] * (int) $item['quantity'];

                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'sale_unit_id' => $item['sale_unit_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'conversion_factor' => $conversionFactor,
                    'line_total' => $lineTotal,
                ]);
            }

            activity('sales_order')
                ->performedOn($order)
                ->causedBy($actor)
                ->withProperties(['status' => SalesOrderStatus::DRAFT->value])
                ->log("Order {$order->id} created as draft");

            return $order->load(['customer', 'user', 'store', 'cashRegisterShift', 'items.productVariant.product.brand', 'items.saleUnit', 'payments']);
        });
    }

    /** @param array<string, mixed> $data */
    public function createPosDraft(array $data, User $actor): SalesOrder
    {
        return DB::transaction(function () use ($data, $actor): SalesOrder {
            $shift = $this->requiredPosShift($actor, true);
            $existingOrder = SalesOrder::query()
                ->where('user_id', $actor->id)
                ->where('cash_register_shift_id', $shift->id)
                ->where('status', SalesOrderStatus::DRAFT)
                ->where('payment_status', SalesOrderPaymentStatus::PENDING)
                ->lockForUpdate()
                ->latest()
                ->first();
            if ($existingOrder !== null) {
                $order = $this->update($existingOrder, $this->preparePosDraftData($data, $shift), $actor);

                return $this->loadPosOrder($order);
            }

            $order = $this->create($this->preparePosDraftData($data, $shift), $actor);

            return $this->loadPosOrder($order);
        });
    }

    /** @param array<string, mixed> $data */
    public function updatePosDraft(SalesOrder $order, array $data, User $actor): SalesOrder
    {
        return DB::transaction(function () use ($order, $data, $actor): SalesOrder {
            $shift = $this->requiredPosShift($actor, true);
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            $this->assertPosDraft($lockedOrder, $actor, $shift);

            $updatedOrder = $this->update($lockedOrder, $this->preparePosDraftData($data, $shift), $actor);

            return $this->loadPosOrder($updatedOrder);
        });
    }

    public function getPosDraft(SalesOrder $order, User $actor): SalesOrder
    {
        $shift = $this->requiredPosShift($actor);
        $this->assertPosDraft($order, $actor, $shift);

        return $this->loadPosOrder($order);
    }

    public function getCurrentPosDraft(User $actor): ?SalesOrder
    {
        $shift = CashRegisterShift::query()
            ->where('user_id', $actor->id)
            ->where('status', CashRegisterShiftStatus::OPEN)
            ->latest('opened_at')
            ->first();
        if ($shift === null) {
            return null;
        }

        $order = SalesOrder::query()
            ->where('user_id', $actor->id)
            ->where('cash_register_shift_id', $shift->id)
            ->whereHas('store.users', fn ($query) => $query->whereKey($actor->id))
            ->where('status', SalesOrderStatus::DRAFT)
            ->where('payment_status', SalesOrderPaymentStatus::PENDING)
            ->latest()
            ->first();

        return $order === null ? null : $this->loadPosOrder($order);
    }

    public function getPosReceipt(SalesOrder $order, User $actor): SalesOrder
    {
        if ($order->user_id !== $actor->id || $order->status !== SalesOrderStatus::COMPLETED) {
            throw new InvalidArgumentException('This receipt is not available for the current cashier.');
        }

        return $this->loadPosOrder($order);
    }

    /** @param array<string, mixed> $data */
    public function completePosSale(SalesOrder $order, array $data, User $actor): SalesOrder
    {
        return DB::transaction(function () use ($order, $data, $actor): SalesOrder {
            $shift = $this->requiredPosShift($actor, true);
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            $this->assertPosDraft($lockedOrder, $actor, $shift);
            $this->assertPosItemsAvailable($lockedOrder);

            $total = round((float) $lockedOrder->total, 2);
            if ($total <= 0) {
                throw new InvalidArgumentException('The sale total must be greater than zero.');
            }

            $payments = $this->posPayments($data, $total);
            $validatedOrder = $this->validate($lockedOrder, $actor);
            $paidOrder = $this->pay($validatedOrder, $payments, $actor);
            $preview = $this->previewFulfillment($paidOrder, $actor);

            return $this->fulfill($paidOrder, $preview['token'], $actor);
        });
    }

    public function discardPosDraft(SalesOrder $order, User $actor): void
    {
        DB::transaction(function () use ($order, $actor): void {
            $shift = $this->requiredPosShift($actor, true);
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            $this->assertPosDraft($lockedOrder, $actor, $shift);
            $this->cancel($lockedOrder, 'Discarded at POS checkout.', $actor);
        });
    }

    /**
     * Update a draft sales order.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SalesOrder $order, array $data, User $actor): SalesOrder
    {
        return DB::transaction(function () use ($order, $data, $actor): SalesOrder {
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== SalesOrderStatus::DRAFT) {
                throw new InvalidArgumentException('Only draft orders can be updated.');
            }

            $items = $data['items'] ?? [];
            $discountType = $data['discount_type'] ?? $lockedOrder->discount_type->value;
            $discountValue = (float) ($data['discount_value'] ?? $lockedOrder->discount_value);
            $taxRate = (float) Setting::get('tax_rate', 0);

            $totals = $this->calculateTotals($items, $discountType, $discountValue, $taxRate);

            $lockedOrder->update([
                'customer_id' => array_key_exists('customer_id', $data) ? $data['customer_id'] : $lockedOrder->customer_id,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'sub_total' => $totals['sub_total'],
                'discount' => $totals['discount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? $lockedOrder->notes,
            ]);

            // Replace items
            $lockedOrder->items()->delete();
            foreach ($items as $item) {
                $conversionFactor = $this->conversionFactorFor($item);
                $lineTotal = (float) $item['unit_price'] * (int) $item['quantity'];

                SalesOrderItem::create([
                    'sales_order_id' => $lockedOrder->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'sale_unit_id' => $item['sale_unit_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'conversion_factor' => $conversionFactor,
                    'line_total' => $lineTotal,
                ]);
            }

            activity('sales_order')
                ->performedOn($lockedOrder)
                ->causedBy($actor)
                ->log("Order {$lockedOrder->id} updated");

            return $this->loadOrder($lockedOrder);
        });
    }

    public function reopen(SalesOrder $order, User $actor): SalesOrder
    {
        return DB::transaction(function () use ($order, $actor): SalesOrder {
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== SalesOrderStatus::VALIDATED) {
                throw new InvalidArgumentException('Only validated orders can be reopened for editing.');
            }

            if ($lockedOrder->payment_status !== SalesOrderPaymentStatus::PENDING || $lockedOrder->payments()->exists()) {
                throw new InvalidArgumentException('Orders with payments cannot be reopened for editing. Create a new sales order for additional products.');
            }

            $lockedOrder->update([
                'status' => SalesOrderStatus::DRAFT,
                'validated_at' => null,
            ]);

            activity('sales_order')
                ->performedOn($lockedOrder)
                ->causedBy($actor)
                ->withProperties(['from' => SalesOrderStatus::VALIDATED->value, 'to' => SalesOrderStatus::DRAFT->value])
                ->log("Order {$lockedOrder->id} reopened for editing");

            return $this->loadOrder($lockedOrder);
        });
    }

    public function validate(SalesOrder $order, User $actor): SalesOrder
    {
        return DB::transaction(function () use ($order, $actor): SalesOrder {
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== SalesOrderStatus::DRAFT) {
                throw new InvalidArgumentException('Only draft orders can be validated.');
            }
            $lockedOrder->load('items');

            foreach ($lockedOrder->items as $item) {
                $requiredQuantity = $item->quantity * $item->conversion_factor;
                $batches = $this->availableBatches($item->product_variant_id, $lockedOrder->store_id)->get();

                if ($batches->sum('remaining_quantity') < $requiredQuantity) {
                    throw new InvalidArgumentException('Insufficient available stock. Please update the order before validating it.');
                }

            }

            $lockedOrder->update(['status' => SalesOrderStatus::VALIDATED, 'validated_at' => now()]);
            activity('sales_order')->performedOn($lockedOrder)->causedBy($actor)->log("Order {$lockedOrder->id} validated");

            return $this->loadOrder($lockedOrder);
        });
    }

    /**
     * @return array{
     *     token: string,
     *     allocations: array<int, array{
     *         sales_order_item_id: int,
     *         batch_id: int,
     *         quantity: int,
     *         product: string,
     *         variant: string,
     *         brand: string|null,
     *         base_unit: string,
     *         batch_identifier: string,
     *         expiry_date: string|null
     *     }>
     * }
     */
    public function previewFulfillment(SalesOrder $order, User $actor): array
    {
        $order = SalesOrder::query()
            ->with(['items.productVariant.product.brand', 'items.productVariant.product.measurementUnit'])
            ->findOrFail($order->id);

        if ($order->status !== SalesOrderStatus::VALIDATED) {
            throw new InvalidArgumentException('Only validated orders can generate a handover list.');
        }
        $this->requirePaidOrNamedCustomer($order);
        $this->requireAssignedCashier($order, $actor);

        $allocations = [];
        foreach ($order->items as $item) {
            $requiredQuantity = $item->quantity * $item->conversion_factor;
            $batches = $this->availableBatches($item->product_variant_id, $order->store_id)->get();

            if ($batches->sum('remaining_quantity') < $requiredQuantity) {
                throw new InvalidArgumentException('Insufficient available stock to generate the handover list.');
            }

            $remainingQuantity = $requiredQuantity;
            foreach ($batches as $batch) {
                if ($remainingQuantity === 0) {
                    break;
                }

                $quantity = min($remainingQuantity, (int) $batch->remaining_quantity);
                $productVariant = $item->productVariant;
                $product = $productVariant?->product;
                if ($product === null) {
                    throw new InvalidArgumentException('The handover list is no longer available. Generate a new list.');
                }

                $measurementUnit = $product->getRelation('measurementUnit');
                $allocations[] = [
                    'sales_order_item_id' => $item->id,
                    'batch_id' => $batch->id,
                    'quantity' => $quantity,
                    'product' => $product->name,
                    'variant' => $productVariant->name ?? $productVariant->identifier ?? '---',
                    'brand' => $product->brand?->name,
                    'base_unit' => $measurementUnit instanceof MeasurementUnit ? $measurementUnit->name : 'Unit',
                    'batch_identifier' => $batch->batch_identifier ?? "#{$batch->id}",
                    'expiry_date' => $batch->expiry_date?->toDateString(),
                ];
                $remainingQuantity -= $quantity;
            }
        }

        $token = (string) Str::uuid();
        Cache::put($this->handoverPreviewCacheKey($token), [
            'order_id' => $order->id,
            'actor_id' => $actor->id,
            'allocations' => array_map(
                fn (array $allocation): array => [
                    'sales_order_item_id' => $allocation['sales_order_item_id'],
                    'batch_id' => $allocation['batch_id'],
                    'quantity' => $allocation['quantity'],
                ],
                $allocations,
            ),
        ], now()->addMinutes(5));

        return ['token' => $token, 'allocations' => $allocations];
    }

    public function fulfill(SalesOrder $order, string $handoverToken, User $actor): SalesOrder
    {
        $preview = Cache::get($this->handoverPreviewCacheKey($handoverToken));
        if (! is_array($preview)
            || ($preview['order_id'] ?? null) !== $order->id
            || ($preview['actor_id'] ?? null) !== $actor->id
            || ! isset($preview['allocations'])
            || ! is_array($preview['allocations'])) {
            throw new InvalidArgumentException('The handover list is no longer available. Generate a new list.');
        }

        $fulfilledOrder = DB::transaction(function () use ($order, $preview, $actor): SalesOrder {
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status !== SalesOrderStatus::VALIDATED) {
                throw new InvalidArgumentException('Only validated orders can be fulfilled.');
            }
            $this->requirePaidOrNamedCustomer($lockedOrder);
            $this->requireAssignedCashier($lockedOrder, $actor);
            $lockedOrder->load('items');

            $items = $lockedOrder->items->keyBy('id');
            $allocations = $preview['allocations'];
            $quantitiesByItem = [];
            $quantitiesByBatch = [];
            foreach ($allocations as $allocation) {
                if (! is_array($allocation)
                    || ! isset($allocation['sales_order_item_id'], $allocation['batch_id'], $allocation['quantity'])
                    || ! is_int($allocation['sales_order_item_id'])
                    || ! is_int($allocation['batch_id'])
                    || ! is_int($allocation['quantity'])
                    || $allocation['quantity'] <= 0) {
                    throw new InvalidArgumentException('The handover list is no longer available. Generate a new list.');
                }

                $item = $items->get($allocation['sales_order_item_id']);
                if ($item === null) {
                    throw new InvalidArgumentException('The handover list is no longer available. Generate a new list.');
                }

                $quantitiesByItem[$item->id] = ($quantitiesByItem[$item->id] ?? 0) + $allocation['quantity'];
                $quantitiesByBatch[$allocation['batch_id']] = ($quantitiesByBatch[$allocation['batch_id']] ?? 0) + $allocation['quantity'];
            }

            foreach ($lockedOrder->items as $item) {
                $requiredQuantity = $item->quantity * $item->conversion_factor;
                if (($quantitiesByItem[$item->id] ?? 0) !== $requiredQuantity) {
                    throw new InvalidArgumentException('The handover list is no longer available. Generate a new list.');
                }
            }

            $batches = Batch::query()
                ->whereIn('id', array_keys($quantitiesByBatch))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($allocations as $allocation) {
                $item = $items->get($allocation['sales_order_item_id']);
                $batch = $batches->get($allocation['batch_id']);
                if ($item === null
                    || $batch === null
                    || $batch->product_variant_id !== $item->product_variant_id
                    || $batch->store_id !== $lockedOrder->store_id
                    || $batch->status !== 'active'
                    || $batch->expiry_date?->lessThan(today())) {
                    throw new InvalidArgumentException('The handover list is no longer available. Generate a new list.');
                }
            }

            foreach ($quantitiesByBatch as $batchId => $quantity) {
                $batch = $batches->get($batchId);
                if ($batch === null || $batch->remaining_quantity < $quantity) {
                    throw new InvalidArgumentException('The handover list is no longer available. Generate a new list.');
                }
            }

            SalesOrderStockAllocation::query()
                ->whereIn('sales_order_item_id', $lockedOrder->items->pluck('id'))
                ->delete();

            foreach ($allocations as $allocation) {
                $item = $items->get($allocation['sales_order_item_id']);
                $item?->stockAllocations()->create([
                    'batch_id' => $allocation['batch_id'],
                    'quantity' => $allocation['quantity'],
                ]);
            }

            foreach ($quantitiesByBatch as $batchId => $quantity) {
                $batch = $batches->get($batchId);
                $batch?->decrement('remaining_quantity', $quantity);
                $batch?->increment('sold_quantity', $quantity);
                $batch?->refresh();
                if ($batch?->remaining_quantity === 0) {
                    $batch->update(['status' => 'closed']);
                }
            }

            foreach ($lockedOrder->items->pluck('product_variant_id')->unique() as $variantId) {
                ProductVariant::query()->whereKey($variantId)->firstOrFail()->recalculateStock();
            }

            $updates = ['fulfilled_by' => $actor->id, 'fulfilled_at' => now()];
            if ($lockedOrder->payment_status === SalesOrderPaymentStatus::PAID) {
                $updates += ['status' => SalesOrderStatus::COMPLETED, 'completed_at' => now()];
            } else {
                $updates['status'] = SalesOrderStatus::FULFILLED;
                CustomerReceivableEntry::create([
                    'customer_id' => $lockedOrder->customer_id,
                    'sales_order_id' => $lockedOrder->id,
                    'user_id' => $actor->id,
                    'type' => 'charge',
                    'amount' => $this->remainingBalance($lockedOrder),
                ]);
            }
            $lockedOrder->update($updates);
            activity('sales_order')->performedOn($lockedOrder)->causedBy($actor)->log("Order {$lockedOrder->id} fulfilled");

            return $this->loadOrder($lockedOrder);
        });

        Cache::forget($this->handoverPreviewCacheKey($handoverToken));

        return $fulfilledOrder;
    }

    /** @param array<int, array{payment_method: string, amount: float, reference?: string|null, tendered_amount?: float|null, change_amount?: float}> $payments */
    public function pay(SalesOrder $order, array $payments, User $actor): SalesOrder
    {
        return DB::transaction(function () use ($order, $payments, $actor): SalesOrder {
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($lockedOrder->status, [SalesOrderStatus::VALIDATED, SalesOrderStatus::FULFILLED], true)) {
                throw new InvalidArgumentException('Only validated or fulfilled orders can receive payments.');
            }
            $remaining = $this->remainingBalance($lockedOrder);
            foreach ($payments as $payment) {
                $amount = (float) $payment['amount'];
                if ($amount > $remaining + 0.01) {
                    throw new InvalidArgumentException('Payments cannot exceed the remaining balance.');
                }
                if ($amount > 0) {
                    $shiftId = null;
                    if ($payment['payment_method'] === PaymentMethod::CASH->value) {
                        $this->requireAssignedCashier($lockedOrder, $actor);
                        $shiftQuery = CashRegisterShift::query()
                            ->where('user_id', $actor->id)
                            ->where('status', CashRegisterShiftStatus::OPEN);
                        if ($lockedOrder->cash_register_shift_id !== null) {
                            $shiftQuery->whereKey($lockedOrder->cash_register_shift_id);
                        }
                        $shiftId = $shiftQuery->lockForUpdate()->value('id');
                        if ($shiftId === null) {
                            throw new InvalidArgumentException('No matching open shift was found for this cash payment.');
                        }
                    }
                    $createdPayment = SalesOrderPayment::create([
                        'sales_order_id' => $lockedOrder->id,
                        'user_id' => $actor->id,
                        'cash_register_shift_id' => $shiftId,
                        'payment_method' => $payment['payment_method'],
                        'amount' => $amount,
                        'reference' => $payment['reference'] ?? null,
                        'tendered_amount' => $payment['tendered_amount'] ?? null,
                        'change_amount' => $payment['change_amount'] ?? 0,
                    ]);
                    if ($lockedOrder->status === SalesOrderStatus::FULFILLED) {
                        CustomerReceivableEntry::create([
                            'customer_id' => $lockedOrder->customer_id,
                            'sales_order_id' => $lockedOrder->id,
                            'sales_order_payment_id' => $createdPayment->id,
                            'user_id' => $actor->id,
                            'type' => 'payment',
                            'amount' => $amount,
                        ]);
                    }
                    $remaining = round($remaining - $amount, 2);
                }
            }
            $updates = ['payment_status' => $remaining <= 0.01 ? SalesOrderPaymentStatus::PAID : SalesOrderPaymentStatus::PARTIALLY_PAID];
            if ($remaining <= 0.01) {
                $updates['paid_at'] = now();
                if ($lockedOrder->status === SalesOrderStatus::FULFILLED) {
                    $updates += ['status' => SalesOrderStatus::COMPLETED, 'completed_at' => now()];
                }
            }
            $lockedOrder->update($updates);
            activity('sales_order')->performedOn($lockedOrder)->causedBy($actor)->log("Order {$lockedOrder->id} paid");

            return $this->loadOrder($lockedOrder);
        });
    }

    /**
     * @param  array<int, array{quantity: int, unit_price: float}>  $items
     * @return array{sub_total: float, discount: float, tax_amount: float, total: float}
     */
    public function calculateTotals(array $items, string $discountType, float $discountValue, float $taxRate): array
    {
        $subTotal = 0.0;

        foreach ($items as $item) {
            $subTotal += (float) $item['unit_price'] * (int) $item['quantity'];
        }

        if ($discountType === DiscountType::FLAT->value) {
            $discount = min($discountValue, $subTotal);
        } else {
            $discount = round($subTotal * (min($discountValue, 100) / 100), 2);
        }

        $taxAmount = round(($subTotal - $discount) * ($taxRate / 100), 2);
        $total = round($subTotal - $discount + $taxAmount, 2);

        return [
            'sub_total' => round($subTotal, 2),
            'discount' => round($discount, 2),
            'tax_amount' => $taxAmount,
            'total' => $total,
        ];
    }

    public function cancel(SalesOrder $order, string $reason, User $actor): void
    {
        DB::transaction(function () use ($order, $reason, $actor): void {
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            if (! in_array($lockedOrder->status, [SalesOrderStatus::DRAFT, SalesOrderStatus::VALIDATED], true)
                || $lockedOrder->payment_status !== SalesOrderPaymentStatus::PENDING) {
                throw new InvalidArgumentException('Only unpaid draft or validated orders can be cancelled.');
            }
            $previousStatus = $lockedOrder->status->value;
            $lockedOrder->update(['status' => SalesOrderStatus::CANCELLED, 'cancelled_at' => now(), 'cancellation_reason' => $reason]);

            activity('sales_order')
                ->performedOn($lockedOrder)
                ->causedBy($actor)
                ->withProperties(['from' => $previousStatus, 'to' => 'cancelled', 'reason' => $reason])
                ->log("Order {$lockedOrder->id} cancelled: {$reason}");
        });
    }

    /** @return \Illuminate\Database\Eloquent\Builder<Batch> */
    private function availableBatches(int $variantId, int $storeId): \Illuminate\Database\Eloquent\Builder
    {
        return Batch::query()
            ->where('product_variant_id', $variantId)
            ->where('store_id', $storeId)
            ->where('status', 'active')
            ->where('remaining_quantity', '>', 0)
            ->where(fn ($query) => $query->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today()))
            ->orderByRaw('expiry_date IS NULL')
            ->orderBy('expiry_date')
            ->orderBy('created_at');
    }

    private function handoverPreviewCacheKey(string $token): string
    {
        return "sales-order-handover-preview:{$token}";
    }

    private function requireAssignedCashier(SalesOrder $order, User $actor): void
    {
        if ($order->user_id !== $actor->id) {
            throw new InvalidArgumentException('Only the assigned cashier can perform this action.');
        }
    }

    private function requirePaidOrNamedCustomer(SalesOrder $order): void
    {
        if ($order->customer_id === null && $order->payment_status !== SalesOrderPaymentStatus::PAID) {
            throw new InvalidArgumentException('Walk-in orders must be paid before handover.');
        }
    }

    private function remainingBalance(SalesOrder $order): float
    {
        return round(max(0, (float) $order->total - (float) $order->payments()->sum('amount')), 2);
    }

    private function loadOrder(SalesOrder $order): SalesOrder
    {
        return $order->fresh(['customer', 'user', 'store', 'cashRegisterShift', 'fulfiller', 'items.productVariant.product.brand', 'items.saleUnit', 'items.stockAllocations.batch', 'payments.user', 'payments.cashRegisterShift', 'receivableEntries']) ?? $order;
    }

    private function loadPosOrder(SalesOrder $order): SalesOrder
    {
        return $order->fresh([
            'customer',
            'user',
            'store',
            'cashRegisterShift.register',
            'fulfiller',
            'items.productVariant.product.brand',
            'items.productVariant.product.measurementUnit',
            'items.productVariant.values',
            'items.productVariant.activeSaleUnits',
            'items.productVariant.batches' => fn (HasMany $query): HasMany => $query
                ->where('store_id', $order->store_id)
                ->where('status', 'active'),
            'items.saleUnit',
            'items.stockAllocations.batch',
            'payments.user',
            'payments.cashRegisterShift',
        ]) ?? $order;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePosDraftData(array $data, CashRegisterShift $shift): array
    {
        $register = $shift->register;
        if ($register === null) {
            throw new InvalidArgumentException('The current shift has no cash register.');
        }

        return [
            'customer_id' => ($data['is_walk_in'] ?? false) ? null : ($data['customer_id'] ?? null),
            'store_id' => $register->store_id,
            'cash_register_shift_id' => $shift->id,
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'items' => $this->normalizePosItems($data['items']),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{product_variant_id: int, sale_unit_id: int|null, quantity: int, unit_price: float}>
     */
    private function normalizePosItems(array $items): array
    {
        $variantIds = collect($items)->pluck('product_variant_id')->map(fn ($id): int => (int) $id)->unique();
        $variants = ProductVariant::query()
            ->with('activeSaleUnits')
            ->whereKey($variantIds)
            ->where('status', 'active')
            ->whereHas('product', fn ($query) => $query->where('status', 'active'))
            ->get()
            ->keyBy('id');

        return array_map(function (array $item) use ($variants): array {
            $variantId = (int) $item['product_variant_id'];
            $variant = $variants->get($variantId);
            if (! $variant instanceof ProductVariant) {
                throw new InvalidArgumentException('A selected product is no longer available for sale.');
            }

            $saleUnitId = isset($item['sale_unit_id']) ? (int) $item['sale_unit_id'] : null;
            $unitPrice = (float) $variant->price;
            if ($saleUnitId !== null) {
                $saleUnit = $variant->activeSaleUnits->firstWhere('id', $saleUnitId);
                if (! $saleUnit instanceof ProductVariantUnit) {
                    throw new InvalidArgumentException('A selected sale unit is no longer available.');
                }
                $unitPrice = (float) $saleUnit->price;
            }

            return [
                'product_variant_id' => $variantId,
                'sale_unit_id' => $saleUnitId,
                'quantity' => (int) $item['quantity'],
                'unit_price' => $unitPrice,
            ];
        }, $items);
    }

    private function requiredPosShift(User $actor, bool $lock = false): CashRegisterShift
    {
        $query = CashRegisterShift::query()
            ->with('register')
            ->where('user_id', $actor->id)
            ->where('status', CashRegisterShiftStatus::OPEN)
            ->latest('opened_at');

        if ($lock) {
            $query->lockForUpdate();
        }

        $shift = $query->first();
        if ($shift === null || $shift->register === null || ! $actor->stores()->whereKey($shift->register->store_id)->exists()) {
            throw new InvalidArgumentException('No open POS shift was found for this cashier.');
        }

        return $shift;
    }

    private function assertPosDraft(SalesOrder $order, User $actor, CashRegisterShift $shift): void
    {
        $register = $shift->register;
        if ($order->status !== SalesOrderStatus::DRAFT
            || $order->payment_status !== SalesOrderPaymentStatus::PENDING
            || $order->user_id !== $actor->id
            || $order->cash_register_shift_id !== $shift->id
            || $register === null
            || $order->store_id !== $register->store_id) {
            throw new InvalidArgumentException('This POS draft is not available for the current shift.');
        }
    }

    private function assertPosItemsAvailable(SalesOrder $order): void
    {
        $order->load('items');
        $this->normalizePosItems($order->items->map(fn (SalesOrderItem $item): array => [
            'product_variant_id' => $item->product_variant_id,
            'sale_unit_id' => $item->sale_unit_id,
            'quantity' => $item->quantity,
        ])->all());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{payment_method: string, amount: float, reference: string|null, tendered_amount: float|null, change_amount: float}>
     */
    private function posPayments(array $data, float $total): array
    {
        $reference = isset($data['qr_reference']) ? mb_trim((string) $data['qr_reference']) : null;
        $reference = $reference === '' ? null : $reference;

        if ($data['payment_mode'] === PaymentMethod::CASH->value) {
            $received = round((float) $data['cash_received'], 2);
            if ($received + 0.01 < $total) {
                throw new InvalidArgumentException('Cash received must cover the sale total.');
            }

            return [[
                'payment_method' => PaymentMethod::CASH->value,
                'amount' => $total,
                'reference' => null,
                'tendered_amount' => $received,
                'change_amount' => round(max(0, $received - $total), 2),
            ]];
        }

        if ($data['payment_mode'] === PaymentMethod::QR->value) {
            return [[
                'payment_method' => PaymentMethod::QR->value,
                'amount' => $total,
                'reference' => $reference,
                'tendered_amount' => null,
                'change_amount' => 0.0,
            ]];
        }

        $cashAmount = round((float) $data['cash_amount'], 2);
        if ($cashAmount <= 0 || $cashAmount >= $total) {
            throw new InvalidArgumentException('Split cash amount must be less than the sale total.');
        }

        return [
            [
                'payment_method' => PaymentMethod::CASH->value,
                'amount' => $cashAmount,
                'reference' => null,
                'tendered_amount' => $cashAmount,
                'change_amount' => 0.0,
            ],
            [
                'payment_method' => PaymentMethod::QR->value,
                'amount' => round($total - $cashAmount, 2),
                'reference' => $reference,
                'tendered_amount' => null,
                'change_amount' => 0.0,
            ],
        ];
    }

    /** @param array<string, mixed> $item */
    private function conversionFactorFor(array $item): int
    {
        if (($item['sale_unit_id'] ?? null) === null) {
            return 1;
        }

        $saleUnit = ProductVariantUnit::query()
            ->whereKey($item['sale_unit_id'])
            ->where('product_variant_id', $item['product_variant_id'])
            ->first();

        if ($saleUnit === null) {
            throw new InvalidArgumentException('The selected sale unit does not belong to the product variant.');
        }

        return $saleUnit->conversion_factor;
    }
}
