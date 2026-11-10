<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;

/**
 * @phpstan-type ReportPeriod array{start: CarbonImmutable, end: CarbonImmutable, comparison_start: CarbonImmutable|null, comparison_end: CarbonImmutable|null, now: CarbonImmutable, timezone: string, preset: string, comparison: string}
 */
final class ReportingPeriodService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return ReportPeriod
     */
    public function resolve(array $filters): array
    {
        $timezone = (string) Setting::get('timezone', config('app.timezone', 'UTC'));
        $now = CarbonImmutable::now($timezone)->startOfSecond();
        $preset = (string) ($filters['preset'] ?? 'today');
        $start = match ($preset) {
            'last_7_days' => $now->subDays(6)->startOfDay(),
            'last_30_days' => $now->subDays(29)->startOfDay(),
            'month_to_date' => $now->startOfMonth(),
            'custom' => CarbonImmutable::parse((string) $filters['from'], $timezone)->startOfDay(),
            default => $now->startOfDay(),
        };
        $end = $preset === 'custom'
            ? CarbonImmutable::parse((string) $filters['to'], $timezone)->addDay()->startOfDay()->min($now)
            : $now;
        $comparison = (string) ($filters['comparison'] ?? 'automatic');
        $comparisonStart = null;
        $comparisonEnd = null;
        if ($comparison !== 'off') {
            $days = match ($preset) {
                'today', 'last_7_days' => 7,
                'last_30_days' => 30,
                default => max(1, (int) $start->startOfDay()->diffInDays($end->subMicrosecond()->startOfDay()) + 1),
            };
            $comparisonStart = $start->subDays($days);
            $comparisonEnd = $end->subDays($days);
            if ($preset === 'month_to_date') {
                $comparisonStart = $start->subMonthNoOverflow();
                $comparisonEnd = $comparisonStart->addDays($now->day - 1)->setTime($now->hour, $now->minute, $now->second)->min($start);
            }
        }

        return ['start' => $start, 'end' => $end, 'comparison_start' => $comparisonStart, 'comparison_end' => $comparisonEnd,
            'now' => $now, 'timezone' => $timezone, 'preset' => $preset, 'comparison' => $comparison];
    }

    public function storageDate(CarbonImmutable $date): string
    {
        return $date->setTimezone((string) config('app.timezone', 'UTC'))->format('Y-m-d H:i:s');
    }
}
