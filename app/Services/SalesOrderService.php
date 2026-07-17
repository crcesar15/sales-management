<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\SalesOrderStatus;
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
    private const TRANSITION_MAP = [
        'draft' => ['sent', 'paid', 'held', 'cancelled'],
        'held' => ['draft', 'cancelled'],
        'sent' => ['paid', 'cancelled'],
        'paid' => ['cancelled'],
        'cancelled' => [],
    ];

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
            $payments = $data['payments'] ?? [];
            $discountType = $data['discount_type'] ?? DiscountType::FLAT->value;
            $discountValue = (float) ($data['discount_value'] ?? 0);
            $taxRate = (float) Setting::get('tax_rate', 0);

            $totals = $this->calculateTotals($items, $discountType, $discountValue, $taxRate);

            $status = $data['status'] ?? SalesOrderStatus::DRAFT->value;

            $order = SalesOrder::create([
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $actor->id,
                'store_id' => $data['store_id'],
                'cash_register_shift_id' => $data['cash_register_shift_id'] ?? null,
                'status' => $status,
                'discount_type' => $discountType,
                'discount_value' => $discountValue,
                'sub_total' => $totals['sub_total'],
                'discount' => $totals['discount'],
                'tax_amount' => $totals['tax_amount'],
                'total' => $totals['total'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $conversionFactor = (int) ($item['conversion_factor'] ?? 1);
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

            foreach ($payments as $payment) {
                SalesOrderPayment::create([
                    'sales_order_id' => $order->id,
                    'payment_method' => $payment['payment_method'],
                    'amount' => $payment['amount'],
                    'reference' => $payment['reference'] ?? null,
                ]);
            }

            if ($status === SalesOrderStatus::PAID->value) {
                $this->fifoStockDeductionService->deductForOrder($order->load('items'));
            }

            activity('sales_order')
                ->performedOn($order)
                ->causedBy($actor)
                ->withProperties(['status' => $status])
                ->log("Order {$order->id} created with status {$status}");

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
            $payments = $data['payments'] ?? [];
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
                $conversionFactor = (int) ($item['conversion_factor'] ?? 1);
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

            // Replace payments
            $order->payments()->delete();
            foreach ($payments as $payment) {
                SalesOrderPayment::create([
                    'sales_order_id' => $order->id,
                    'payment_method' => $payment['payment_method'],
                    'amount' => $payment['amount'],
                    'reference' => $payment['reference'] ?? null,
                ]);
            }

            activity('sales_order')
                ->performedOn($order)
                ->causedBy($actor)
                ->log("Order {$order->id} updated");

            return $order->fresh(['customer', 'user', 'store', 'cashRegisterShift', 'items.productVariant.product.brand', 'items.saleUnit', 'payments']) ?? $order;
        });
    }

    public function transitionStatus(SalesOrder $order, string $newStatus, User $actor): SalesOrder
    {
        $currentStatus = $order->status->value;

        $this->validateTransition($currentStatus, $newStatus);

        return DB::transaction(function () use ($order, $newStatus, $actor, $currentStatus): SalesOrder {
            if ($newStatus === SalesOrderStatus::PAID->value) {
                $this->fifoStockDeductionService->deductForOrder($order->load('items'));
            }

            $order->update(['status' => $newStatus]);

            activity('sales_order')
                ->performedOn($order)
                ->causedBy($actor)
                ->withProperties(['from' => $currentStatus, 'to' => $newStatus])
                ->log("Order {$order->id} status changed from {$currentStatus} to {$newStatus}");

            return $order->fresh(['customer', 'user', 'store', 'cashRegisterShift', 'items.productVariant.product.brand', 'items.saleUnit', 'payments']) ?? $order;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function holdOrder(array $data, User $actor): SalesOrder
    {
        $data['status'] = SalesOrderStatus::HELD->value;

        return $this->create($data, $actor);
    }

    public function resumeOrder(SalesOrder $order, User $actor): SalesOrder
    {
        return $this->transitionStatus($order, SalesOrderStatus::DRAFT->value, $actor);
    }

    /**
     * @param  array<int, array{product_variant_id: int, quantity: int, unit_price: float, conversion_factor?: int}>  $items
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
        if ($order->status === SalesOrderStatus::CANCELLED) {
            throw new InvalidArgumentException('Order is already cancelled.');
        }

        $this->validateTransition($order->status->value, SalesOrderStatus::CANCELLED->value);

        DB::transaction(function () use ($order, $reason, $actor): void {
            $previousStatus = $order->status->value;

            $order->update(['status' => SalesOrderStatus::CANCELLED->value]);

            activity('sales_order')
                ->performedOn($order)
                ->causedBy($actor)
                ->withProperties(['from' => $previousStatus, 'to' => 'cancelled', 'reason' => $reason])
                ->log('Order ' . $order->id . ' cancelled' . ($reason ? ": {$reason}" : ''));
        });
    }

    private function validateTransition(string $from, string $to): void
    {
        $allowed = self::TRANSITION_MAP[$from] ?? [];

        if (! in_array($to, $allowed, true)) {
            throw new InvalidArgumentException("Cannot transition order from {$from} to {$to}.");
        }
    }
}
