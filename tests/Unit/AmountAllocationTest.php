<?php

declare(strict_types=1);

use App\Services\AmountAllocationService;

it('allocates every cent deterministically without assigning money to zero value lines', function () {
    $allocation = (new AmountAllocationService)->allocate('0.02', [3 => '1.00', 1 => '1.00', 2 => '1.00', 4 => '0.00']);
    expect($allocation)->toBe([1 => '0.01', 2 => '0.01', 3 => '0.00', 4 => '0.00']);
});

it('handles fully discounted orders and rejects impossible allocations', function () {
    $service = new AmountAllocationService;
    expect($service->allocate('0.00', [1 => '0.00']))->toBe([1 => '0.00']);
    expect(fn () => $service->allocate('1.00', [1 => '0.00']))->toThrow(InvalidArgumentException::class);
});
