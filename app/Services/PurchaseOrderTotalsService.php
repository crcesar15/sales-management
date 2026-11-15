<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DiscountType;
use App\Models\ProductVariantUnit;
use App\Models\PurchaseOrder;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

/**
 * @phpstan-type LineTotals array{gross_total: string, discount_type: string, discount_value: string, discount_amount: string, total: string, allocated_discount: string, final_total: string}
 * @phpstan-type OrderTotals array{items: array<int, LineTotals>, gross_sub_total: string, line_discount_total: string, sub_total: string, discount_type: string, discount_value: string, discount: string, total: string}
 */
final class PurchaseOrderTotalsService
{
    public function __construct(private readonly AmountAllocationService $allocator) {}

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function preview(array $data, PricingRecommendationService $pricing): array
    {
        $totals = $this->forInput($data);
        $items = $this->effectiveItems($data);
        /** @var list<array{product_variant_id: int, unit_id?: int|null, price: float|int|string}> $pricingItems */
        $pricingItems = array_values($items);
        $replacement = $pricing->preview($pricingItems);
        $units = ProductVariantUnit::query()->whereKey(array_filter(array_column($items, 'unit_id')))->get()->keyBy('id');
        $lines = [];
        foreach ($totals['items'] as $index => $line) {
            $item = $items[$index];
            $factor = isset($item['unit_id']) ? ($units->get($item['unit_id'])->conversion_factor ?? 1) : 1;
            $quantity = BigDecimal::of((string) $item['quantity']);
            $unitCost = BigDecimal::of($line['final_total'])->dividedBy($quantity, 12, RoundingMode::HalfUp);
            $lines[] = [...$line,
                'actual_unit_cost' => $unitCost->toFloat(),
                'actual_base_unit_cost' => $unitCost->dividedBy($factor, 12, RoundingMode::HalfUp)->toFloat(),
            ];
        }

        return [...$totals, 'items' => $lines, 'replacement_pricing' => $replacement];
    }

    /** @param array<string, mixed> $item
     * @return LineTotals
     */
    public function line(array $item): array
    {
        $price = $this->money((string) ($item['price'] ?? '0'));
        $quantity = $this->number((string) ($item['quantity'] ?? '0'));
        if (! $quantity->isPositive() || $quantity->isGreaterThan(999999) || ! $quantity->isEqualTo($quantity->toScale(0, RoundingMode::Down))) {
            throw new InvalidArgumentException('Purchase quantities must be positive whole purchasing units.');
        }
        $gross = $price->multipliedBy($quantity)->toScale(2, RoundingMode::HalfUp);
        if ($gross->isGreaterThan('99999999.99')) {
            throw new InvalidArgumentException('The line total exceeds the supported monetary limit.');
        }
        $type = (string) (($item['discount_type'] ?? null) instanceof DiscountType ? $item['discount_type']->value : ($item['discount_type'] ?? 'flat'));
        $value = $this->money((string) ($item['discount_value'] ?? '0'));
        $discount = $this->discount($gross, $type, $value);
        $net = (string) $gross->minus($discount)->toScale(2);

        return ['gross_total' => (string) $gross, 'discount_type' => $type, 'discount_value' => (string) $value,
            'discount_amount' => (string) $discount, 'total' => $net, 'allocated_discount' => '0.00', 'final_total' => $net];
    }

    /** @param array<int, array<string, mixed>> $items
     * @return OrderTotals
     */
    public function calculate(array $items, string $discountType = 'flat', string $discountValue = '0'): array
    {
        $gross = BigDecimal::zero();
        $lineDiscounts = BigDecimal::zero();
        $subTotal = BigDecimal::zero();
        $lines = [];
        $weights = [];
        foreach ($items as $index => $item) {
            $line = $this->line($item);
            $lines[$index] = $line;
            $gross = $gross->plus($line['gross_total']);
            $lineDiscounts = $lineDiscounts->plus($line['discount_amount']);
            $subTotal = $subTotal->plus($line['total']);
            $weights[$index] = $line['total'];
        }
        if ($gross->isGreaterThan('99999999.99')) {
            throw new InvalidArgumentException('The order subtotal exceeds the supported monetary limit.');
        }
        $value = $this->money($discountValue);
        $discount = $this->discount($subTotal, $discountType, $value);
        $netAmounts = $this->netAmounts($weights, (string) $discount);
        foreach ($lines as $index => &$line) {
            $line['allocated_discount'] = (string) BigDecimal::of($line['total'])->minus($netAmounts[$index])->toScale(2);
            $line['final_total'] = $netAmounts[$index];
        }
        unset($line);

        return ['items' => $lines, 'gross_sub_total' => (string) $gross->toScale(2), 'line_discount_total' => (string) $lineDiscounts->toScale(2),
            'sub_total' => (string) $subTotal->toScale(2), 'discount_type' => $discountType, 'discount_value' => (string) $value,
            'discount' => (string) $discount, 'total' => (string) $subTotal->minus($discount)->toScale(2)];
    }

    /** @param array<int, string> $lineTotals
     * @return array<int, string>
     */
    public function netAmounts(array $lineTotals, string $orderDiscount): array
    {
        $discounts = $this->allocator->allocate($orderDiscount, $lineTotals);
        $net = [];
        foreach ($lineTotals as $id => $amount) {
            $value = BigDecimal::of($amount)->minus($discounts[$id]);
            if ($value->isNegative()) {
                throw new InvalidArgumentException('The order discount exceeds the net subtotal.');
            }
            $net[$id] = (string) $value->toScale(2);
        }

        return $net;
    }

    /** @param array<string, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    public function effectiveItems(array $data, ?PurchaseOrder $order = null): array
    {
        $order?->loadMissing('lineItems');
        if (! array_key_exists('items', $data)) {
            return $order?->lineItems->mapWithKeys(fn ($line): array => [$line->id => [
                'price' => $line->price, 'quantity' => $line->quantity, 'discount_type' => $line->discount_type->value,
                'discount_value' => $line->discount_value, 'catalog_id' => $line->catalog_id,
            ]])->all() ?? [];
        }
        $items = array_values($data['items']);
        foreach ($items as &$item) {
            $previous = isset($item['id']) ? $order?->lineItems->firstWhere('id', $item['id']) : null;
            if ($previous === null && isset($item['catalog_id']) && $order !== null) {
                $matches = $order->lineItems->where('catalog_id', $item['catalog_id']);
                $previous = $matches->count() === 1 ? $matches->first() : null;
            }
            $item['discount_type'] ??= $previous?->discount_type->value ?? 'flat';
            $item['discount_value'] = array_key_exists('discount_value', $item) ? ($item['discount_value'] ?? '0') : ($previous->discount_value ?? '0');
            $item['pricing_reference'] ??= $previous->pricing_reference ?? false;
        }
        unset($item);

        return $items;
    }

    /** @param array<string, mixed> $data
     * @return array{string, string}
     */
    public function headerDiscount(array $data, ?PurchaseOrder $order = null): array
    {
        // Existing amount-only API input remains an explicit flat discount.
        if (array_key_exists('discount', $data) && ! array_key_exists('discount_value', $data)) {
            return ['flat', (string) ($data['discount'] ?? '0')];
        }

        return [(string) ($data['discount_type'] ?? $order?->discount_type->value ?? 'flat'), (string) (array_key_exists('discount_value', $data) ? ($data['discount_value'] ?? '0') : ($order->discount_value ?? '0'))];
    }

    /** @param array<string, mixed> $data
     * @return OrderTotals
     */
    public function forInput(array $data, ?PurchaseOrder $order = null): array
    {
        [$type, $value] = $this->headerDiscount($data, $order);

        return $this->calculate($this->effectiveItems($data, $order), $type, $value);
    }

    public function discount(BigDecimal $base, string $type, BigDecimal $value): BigDecimal
    {
        if ($value->isNegative() || ! in_array($type, ['flat', 'percentage'], true)) {
            throw new InvalidArgumentException('Select a valid discount type and a nonnegative value.');
        }
        if (($type === 'percentage' && $value->isGreaterThan(100)) || ($type === 'flat' && $value->isGreaterThan($base))) {
            throw new InvalidArgumentException($type === 'percentage' ? 'The discount percentage cannot exceed 100.' : 'The discount amount cannot exceed the amount before this discount.');
        }

        return $type === 'percentage' ? $base->multipliedBy($value)->dividedBy(100, 2, RoundingMode::HalfUp) : $value->toScale(2);
    }

    public function money(string $value): BigDecimal
    {
        $number = $this->number($value);
        if ($number->isNegative() || ! $number->isEqualTo($number->toScale(2, RoundingMode::HalfUp)) || $number->isGreaterThan('99999999.99')) {
            throw new InvalidArgumentException('Amounts must be nonnegative, within the monetary limit, and have at most two decimal places.');
        }

        return $number->toScale(2);
    }

    private function number(string $value): BigDecimal
    {
        try {
            return BigDecimal::of($value);
        } catch (MathException $exception) {
            throw new InvalidArgumentException('Enter a valid numeric amount.', previous: $exception);
        }
    }
}
