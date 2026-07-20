<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\SalesOrderPaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Models\ProductVariantUnit;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderPayment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class SalesOrderService
{
    public function __construct(
        private readonly FifoStockDeductionService $fifoStockDeductionService,
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

    /**
     * Update a draft sales order.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SalesOrder $order, array $data, User $actor): SalesOrder
    {
        if ($order->status !== SalesOrderStatus::DRAFT) {
            throw new InvalidArgumentException('Only draft orders can be updated.');
        }

        return DB::transaction(function () use ($order, $data, $actor): SalesOrder {
            $items = $data['items'] ?? [];
            $discountType = $data['discount_type'] ?? $order->discount_type->value;
            $discountValue = (float) ($data['discount_value'] ?? $order->discount_value);
            $taxRate = (float) Setting::get('tax_rate', 0);

            $totals = $this->calculateTotals($items, $discountType, $discountValue, $taxRate);

            $order->update([
                'customer_id' => $data['customer_id'] ?? $order->customer_id,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'sub_total' => $totals['sub_total'],
                'discount' => $totals['discount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? $order->notes,
            ]);

            // Replace items
            $order->items()->delete();
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
                ->log("Order {$order->id} updated");

            return $order->fresh(['customer', 'user', 'store', 'cashRegisterShift', 'items.productVariant.product.brand', 'items.saleUnit', 'payments']) ?? $order;
        });
    }

    /** @param array<string, mixed> $data */
    public function updateCheckout(SalesOrder $order, array $data, User $actor): SalesOrder
    {
        if ($order->status !== SalesOrderStatus::DRAFT) {
            throw new InvalidArgumentException('Only draft orders can be updated.');
        }

        return DB::transaction(function () use ($order, $data, $actor): SalesOrder {
            $totals = $this->calculateTotals(
                $order->items->map(fn (SalesOrderItem $item): array => [
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                ])->all(),
                $data['discount_type'],
                (float) $data['discount_value'],
                (float) Setting::get('tax_rate', 0),
            );

            $order->update([
                'customer_id' => $data['customer_id'] ?? null,
                'discount_type' => $data['discount_type'],
                'discount_value' => $data['discount_value'],
                'sub_total' => $totals['sub_total'],
                'discount' => $totals['discount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? null,
            ]);

            activity('sales_order')
                ->performedOn($order)
                ->causedBy($actor)
                ->log("Order {$order->id} checkout details updated");

            return $order->fresh(['customer', 'user', 'store', 'cashRegisterShift', 'items.productVariant.product.brand', 'items.saleUnit', 'payments']) ?? $order;
        });
    }

    public function confirm(SalesOrder $order, User $actor): SalesOrder
    {
        if ($order->status !== SalesOrderStatus::DRAFT) {
            throw new InvalidArgumentException('Only draft orders can be confirmed.');
        }

        return DB::transaction(function () use ($order, $actor): SalesOrder {
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            $lockedOrder->load('items');
            $this->fifoStockDeductionService->deductForOrder($lockedOrder);
            $lockedOrder->update(['status' => SalesOrderStatus::CONFIRMED, 'confirmed_at' => now()]);
            activity('sales_order')->performedOn($lockedOrder)->causedBy($actor)->log("Order {$lockedOrder->id} confirmed");

            return $lockedOrder->fresh(['customer', 'user', 'store', 'cashRegisterShift', 'items.productVariant.product.brand', 'items.saleUnit', 'payments']) ?? $lockedOrder;
        });
    }

    public function deliver(SalesOrder $order, User $actor): SalesOrder
    {
        if ($order->status !== SalesOrderStatus::CONFIRMED) {
            throw new InvalidArgumentException('Only confirmed orders can be delivered.');
        }

        return DB::transaction(function () use ($order, $actor): SalesOrder {
            $order->update(['status' => SalesOrderStatus::DELIVERED, 'delivered_at' => now()]);
            activity('sales_order')->performedOn($order)->causedBy($actor)->log("Order {$order->id} delivered");

            return $order->fresh(['customer', 'user', 'store', 'cashRegisterShift', 'items.productVariant.product.brand', 'items.saleUnit', 'payments']) ?? $order;
        });
    }

    /** @param array<int, array{payment_method: string, amount: float, reference?: string|null}> $payments */
    public function pay(SalesOrder $order, array $payments, User $actor): SalesOrder
    {
        if (! in_array($order->status, [SalesOrderStatus::CONFIRMED, SalesOrderStatus::DELIVERED], true)) {
            throw new InvalidArgumentException('Only confirmed or delivered orders can be paid.');
        }
        if ($order->payment_status !== SalesOrderPaymentStatus::PENDING) {
            throw new InvalidArgumentException('Order has already been paid.');
        }

        return DB::transaction(function () use ($order, $payments, $actor): SalesOrder {
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->payment_status !== SalesOrderPaymentStatus::PENDING) {
                throw new InvalidArgumentException('Order has already been paid.');
            }

            $remaining = (float) $lockedOrder->total;
            foreach ($payments as $payment) {
                $amount = (float) $payment['amount'];
                if ($payment['payment_method'] !== 'cash' && $amount > $remaining + 0.01) {
                    throw new InvalidArgumentException('Non-cash payments cannot exceed the remaining balance.');
                }
                $appliedAmount = min($amount, $remaining);
                if ($appliedAmount > 0) {
                    SalesOrderPayment::create([
                        'sales_order_id' => $lockedOrder->id,
                        'payment_method' => $payment['payment_method'],
                        'amount' => $appliedAmount,
                        'reference' => $payment['reference'] ?? null,
                    ]);
                    $remaining = round($remaining - $appliedAmount, 2);
                }
            }
            if ($remaining > 0.01) {
                throw new InvalidArgumentException('Payments must cover the order total.');
            }

            $lockedOrder->update(['payment_status' => SalesOrderPaymentStatus::PAID, 'paid_at' => now()]);
            activity('sales_order')->performedOn($lockedOrder)->causedBy($actor)->log("Order {$lockedOrder->id} paid");

            return $lockedOrder->fresh(['customer', 'user', 'store', 'cashRegisterShift', 'items.productVariant.product.brand', 'items.saleUnit', 'payments']) ?? $lockedOrder;
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
            $discount = round($subTotal * ($discountValue / 100), 2);
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

    public function cancel(SalesOrder $order, ?string $reason, User $actor): void
    {
        DB::transaction(function () use ($order, $reason, $actor): void {
            $lockedOrder = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            if ($lockedOrder->status === SalesOrderStatus::CANCELLED) {
                throw new InvalidArgumentException('Order is already cancelled.');
            }
            if ($lockedOrder->payment_status === SalesOrderPaymentStatus::PAID) {
                throw new InvalidArgumentException('Paid orders require a refund before cancellation.');
            }

            $previousStatus = $lockedOrder->status->value;
            if (in_array($lockedOrder->status, [SalesOrderStatus::CONFIRMED, SalesOrderStatus::DELIVERED], true)) {
                $lockedOrder->load('items');
                $this->fifoStockDeductionService->restoreForOrder($lockedOrder);
            }
            $lockedOrder->update(['status' => SalesOrderStatus::CANCELLED, 'cancelled_at' => now()]);

            activity('sales_order')
                ->performedOn($lockedOrder)
                ->causedBy($actor)
                ->withProperties(['from' => $previousStatus, 'to' => 'cancelled', 'reason' => $reason])
                ->log('Order ' . $lockedOrder->id . ' cancelled' . ($reason ? ": {$reason}" : ''));
        });
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
