<?php

declare(strict_types=1);

use App\Services\AmountAllocationService;
use App\Services\PurchaseOrderTotalsService;

it('applies item discounts before the order discount and reconciles allocations', function () {
    $calculator = new PurchaseOrderTotalsService(new AmountAllocationService);
    $totals = $calculator->calculate([
        ['quantity' => 10, 'price' => '20', 'discount_type' => 'percentage', 'discount_value' => '10'],
        ['quantity' => 5, 'price' => '40', 'discount_type' => 'flat', 'discount_value' => '50'],
    ], 'percentage', '10');
    expect($totals)->toMatchArray(['gross_sub_total' => '400.00', 'line_discount_total' => '70.00', 'sub_total' => '330.00', 'discount' => '33.00', 'total' => '297.00'])
        ->and($totals['items'][0])->toMatchArray(['discount_amount' => '20.00', 'total' => '180.00', 'allocated_discount' => '18.00', 'final_total' => '162.00'])
        ->and($totals['items'][1])->toMatchArray(['discount_amount' => '50.00', 'total' => '150.00', 'allocated_discount' => '15.00', 'final_total' => '135.00']);
});

it('stacks percentages sequentially and rounds monetary values half up', function () {
    $calculator = new PurchaseOrderTotalsService(new AmountAllocationService);
    expect($calculator->calculate([['quantity' => 1, 'price' => '100', 'discount_type' => 'percentage', 'discount_value' => '10']], 'percentage', '10')['total'])->toBe('81.00')
        ->and($calculator->line(['quantity' => 1, 'price' => '0.05', 'discount_type' => 'percentage', 'discount_value' => '10'])['discount_amount'])->toBe('0.01');
});

it('allocates rounding remainders deterministically without charging free lines', function () {
    $calculator = new PurchaseOrderTotalsService(new AmountAllocationService);
    $totals = $calculator->calculate([
        ['quantity' => 1, 'price' => '10', 'discount_type' => 'percentage', 'discount_value' => '100'],
        ['quantity' => 1, 'price' => '0.01'], ['quantity' => 1, 'price' => '0.01'], ['quantity' => 1, 'price' => '0.01'],
    ], 'flat', '0.01');
    expect(array_column($totals['items'], 'allocated_discount'))->toBe(['0.00', '0.01', '0.00', '0.00'])
        ->and($totals['total'])->toBe('0.02');
});

it('supports fully discounted orders and zero prices without division errors', function () {
    $calculator = new PurchaseOrderTotalsService(new AmountAllocationService);
    $totals = $calculator->calculate([['quantity' => 2, 'price' => '10', 'discount_type' => 'percentage', 'discount_value' => '100'], ['quantity' => 1, 'price' => '0']], 'percentage', '100');
    expect($totals['total'])->toBe('0.00')->and(array_column($totals['items'], 'final_total'))->toBe(['0.00', '0.00']);
});

it('rejects invalid discounts, money precision and unsupported quantities', function (array $line) {
    $calculator = new PurchaseOrderTotalsService(new AmountAllocationService);
    expect(fn () => $calculator->line($line))->toThrow(InvalidArgumentException::class);
})->with([
    'excessive flat' => [['quantity' => 1, 'price' => '10', 'discount_value' => '11']],
    'excessive percentage' => [['quantity' => 1, 'price' => '10', 'discount_type' => 'percentage', 'discount_value' => '100.01']],
    'negative discount' => [['quantity' => 1, 'price' => '10', 'discount_value' => '-1']],
    'invalid precision' => [['quantity' => 1, 'price' => '10.001']],
    'fractional purchasing quantity' => [['quantity' => '0.5', 'price' => '10']],
    'unknown type' => [['quantity' => 1, 'price' => '10', 'discount_type' => 'other']],
    'overflow' => [['quantity' => 2, 'price' => '99999999.99']],
    'quantity overflow on free stock' => [['quantity' => 1000000, 'price' => '0']],
]);
