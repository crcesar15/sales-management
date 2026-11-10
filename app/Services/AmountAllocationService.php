<?php

declare(strict_types=1);

namespace App\Services;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use InvalidArgumentException;

final class AmountAllocationService
{
    /**
     * Largest-remainder allocation in cents, with stable ID-based tie breaking.
     *
     * @param  array<int, string>  $weights
     * @return array<int, string>
     */
    public function allocate(string $amount, array $weights): array
    {
        ksort($weights);
        $cents = BigDecimal::of($amount)->multipliedBy(100)->toScale(0, RoundingMode::HalfUp)->toInt();
        $sum = BigDecimal::zero();
        foreach ($weights as $weight) {
            if (BigDecimal::of($weight)->isNegative()) {
                throw new InvalidArgumentException('Allocation weights cannot be negative.');
            }
            $sum = $sum->plus($weight);
        }
        if ($cents < 0 || ($sum->isZero() && $cents !== 0)) {
            throw new InvalidArgumentException('The amount cannot be allocated to these weights.');
        }

        $allocated = [];
        $remainders = [];
        foreach ($weights as $id => $weight) {
            $numerator = BigDecimal::of($weight)->multipliedBy($cents);
            $share = $sum->isZero() ? 0 : $numerator->dividedBy($sum, 0, RoundingMode::Down)->toInt();
            $allocated[$id] = $share;
            $remainders[$id] = $numerator->minus($sum->multipliedBy($share));
        }
        uksort($remainders, fn (int $a, int $b): int => $remainders[$b]->compareTo($remainders[$a]) ?: $a <=> $b);
        $remaining = $cents - array_sum($allocated);
        foreach (array_keys($remainders) as $id) {
            if ($remaining-- <= 0) {
                break;
            }
            $allocated[$id]++;
        }

        return array_map(fn (int $value): string => (string) BigDecimal::of($value)->dividedBy(100, 2), $allocated);
    }
}
