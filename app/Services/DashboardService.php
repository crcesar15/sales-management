<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PermissionsEnum;
use App\Models\Setting;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

/** @phpstan-import-type ReportPeriod from ReportingPeriodService */
final class DashboardService
{
    private const COMPLETE_COST = 'a.missing_costs = 0 AND a.allocated_quantity = i.quantity * i.conversion_factor AND i.net_sales_amount IS NOT NULL';

    public function __construct(
        private readonly ReportingScopeService $scope,
        private readonly ReportingPeriodService $periods,
        private readonly StockAlertService $alerts,
    ) {}

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function snapshot(User $user, array $filters): array
    {
        $storeId = isset($filters['store_id']) ? (int) $filters['store_id'] : null;
        $storeIds = $this->scope->storeIds($user, $storeId);
        $period = $this->periods->resolve($filters);
        $sales = $user->can(PermissionsEnum::SALES_VIEW->value);
        $profit = $sales && $user->can(PermissionsEnum::DASHBOARD_PROFIT_VIEW->value);
        $inventory = $user->can(PermissionsEnum::STOCK_ALERTS_VIEW->value);
        $shifts = $user->can(PermissionsEnum::SHIFTS_VIEW->value);

        // A short read transaction keeps the widgets on one database snapshot.
        return DB::transaction(function () use ($user, $filters, $storeId, $storeIds, $period, $sales, $profit, $inventory, $shifts): array {
            $orders = $this->orders($user, $storeIds, $period['start'], $period['end']);
            $current = $sales ? $this->summary($orders, $profit) : null;
            $previousOrders = $period['comparison_start'] !== null && $period['comparison_end'] !== null
                ? $this->orders($user, $storeIds, $period['comparison_start'], $period['comparison_end']) : null;

            return [
                'generated_at' => $period['now']->toISOString(),
                'filters' => ['store_id' => $storeId, 'preset' => $period['preset'], 'comparison' => $period['comparison'],
                    'from' => $period['start']->toDateString(), 'to' => $period['end']->subMicrosecond()->max($period['start'])->toDateString()],
                'period' => ['start' => $period['start']->toISOString(), 'end' => $period['end']->toISOString(),
                    'comparison_start' => $period['comparison_start']?->toISOString(), 'comparison_end' => $period['comparison_end']?->toISOString(),
                    'timezone' => $period['timezone']],
                'capabilities' => ['sales' => $sales, 'profit' => $profit, 'inventory' => $inventory, 'shifts' => $shifts,
                    'inventory_details' => $user->can(PermissionsEnum::INVENTORY_VIEW->value),
                    'all_sales' => $user->can(PermissionsEnum::SALES_VIEW_ALL->value)],
                'store_count' => count($storeIds),
                'sales' => $current,
                'comparison' => $sales && $previousOrders !== null ? $this->summary($previousOrders, $profit) : null,
                'trend' => $sales ? $this->trend($orders, $previousOrders, $period) : null,
                'products' => $sales ? $this->products($orders, $profit) : null,
                'inventory' => $inventory ? $this->inventory($storeIds, $period) : null,
                'collections' => $sales ? $this->collections($user, $storeIds, $period) : null,
                'shifts' => $shifts ? $this->shifts($storeIds, $period) : null,
                'stores' => $sales && $storeId === null ? $this->storePerformance($user, $storeIds, $orders, $profit, (int) ($filters['store_page'] ?? 1)) : null,
            ];
        });
    }

    /** @param list<int> $storeIds */
    private function orders(User $user, array $storeIds, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return DB::table('sales_orders')->whereIn('store_id', $storeIds)
            ->when(! $user->can(PermissionsEnum::SALES_VIEW_ALL->value), fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('status', ['fulfilled', 'completed'])
            ->where('fulfilled_at', '>=', $this->periods->storageDate($start))
            ->where('fulfilled_at', '<', $this->periods->storageDate($end));
    }

    private function lines(Builder $orders): Builder
    {
        $allocations = DB::table('sales_order_stock_allocations')
            ->select('sales_order_item_id')->selectRaw('SUM(quantity * unit_cost) as cost, SUM(quantity) as allocated_quantity, SUM(CASE WHEN unit_cost IS NULL THEN 1 ELSE 0 END) as missing_costs')
            ->whereIn('sales_order_item_id', DB::table('sales_order_items')->select('id')->whereIn('sales_order_id', (clone $orders)->select('id')))
            ->groupBy('sales_order_item_id');

        return DB::table('sales_order_items as i')
            ->joinSub((clone $orders)->select('id', 'store_id'), 'o', 'o.id', '=', 'i.sales_order_id')
            ->leftJoinSub($allocations, 'a', 'a.sales_order_item_id', '=', 'i.id');
    }

    private function lineTotals(Builder $query): Builder
    {
        $complete = self::COMPLETE_COST;

        return $query->selectRaw("COUNT(*) as line_count, COUNT(DISTINCT i.sales_order_id) as orders_with_lines,
            SUM(CASE WHEN {$complete} THEN 0 ELSE 1 END) as missing_lines,
            SUM(COALESCE(i.net_sales_amount, 0)) as revenue,
            SUM(CASE WHEN {$complete} THEN COALESCE(a.cost, 0) ELSE 0 END) as known_cost,
            SUM(CASE WHEN {$complete} THEN i.net_sales_amount ELSE 0 END) as known_revenue");
    }

    /** @return array<string, mixed> */
    private function summary(Builder $orders, bool $profit): array
    {
        $row = (clone $orders)->selectRaw('COUNT(*) as order_count, COALESCE(SUM(total - tax_amount), 0) as net_sales')->first();
        $count = (int) ($row->order_count ?? 0);
        $net = $this->money($row->net_sales ?? 0);

        return ['net_sales' => $net, 'order_count' => $count, 'average_sale' => $count > 0 ? round($net / $count, 2) : null,
            'profitability' => $profit ? $this->profitability($this->lineTotals($this->lines($orders))->first(), $count, $net) : null];
    }

    /** @return array<string, mixed> */
    private function profitability(?stdClass $row, int $orders, float $net): array
    {
        $missing = (int) ($row->missing_lines ?? 0) + max(0, $orders - (int) ($row->orders_with_lines ?? 0));
        $knownCost = $this->money($row->known_cost ?? 0);
        $knownRevenue = $this->money($row->known_revenue ?? 0);
        $complete = $missing === 0;
        $profit = $this->money(BigDecimal::of((string) $net)->minus((string) $knownCost)->toString());
        $lines = (int) ($row->line_count ?? 0);

        return ['complete' => $complete, 'gross_profit' => $complete ? $profit : null,
            'margin' => $complete && $net > 0 ? round($profit / $net * 100, 2) : null,
            'cost_of_goods' => $complete ? $knownCost : null, 'known_cost' => $knownCost,
            'known_profit' => $this->money(BigDecimal::of((string) $knownRevenue)->minus((string) $knownCost)->toString()),
            'missing_lines' => $missing, 'cost_coverage' => $complete ? 100 : ($lines > 0 ? round(max(0, $lines - $missing) / $lines * 100, 1) : 0)];
    }

    /** @return list<array<string, mixed>> */
    private function products(Builder $orders, bool $profit): array
    {
        $query = $this->lines($orders)->join('product_variants as v', 'v.id', '=', 'i.product_variant_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')
            ->groupBy('v.id', 'v.identifier', 'p.name')
            ->select('v.id as variant_id', 'v.identifier', 'p.name');
        if ($profit) {
            $query = $this->lineTotals($query);
        } else {
            $query->selectRaw('SUM(COALESCE(i.net_sales_amount, 0)) as revenue');
        }

        return array_values($query->orderByDesc('revenue')->orderBy('v.id')->limit(8)->get()->map(function (stdClass $row) use ($profit): array {
            $net = $this->money($row->revenue ?? 0);

            return ['variant_id' => (int) $row->variant_id, 'name' => $row->name, 'identifier' => $row->identifier, 'net_sales' => $net,
                'profitability' => $profit ? $this->profitability($row, (int) $row->orders_with_lines, $net) : null];
        })->all());
    }

    /** @param ReportPeriod $period
     * @return array<string, mixed>
     */
    private function trend(Builder $orders, ?Builder $previous, array $period): array
    {
        $hourly = $period['start']->toDateString() === $period['end']->subMicrosecond()->toDateString();
        $step = $hourly ? 'hour' : ($period['start']->diffInDays($period['end']) > 90 ? 'week' : 'day');
        $current = $this->buckets($orders, $period['start'], $period['end'], $step);
        $comparison = $previous !== null && $period['comparison_start'] !== null && $period['comparison_end'] !== null
            ? $this->buckets($previous, $period['comparison_start'], $period['comparison_end'], $step) : [];

        return ['granularity' => $step, 'points' => array_map(fn (array $point, int $i): array => [
            'at' => $point['at'], 'sales' => $point['sales'], 'comparison' => $comparison[$i]['sales'] ?? null,
            'comparison_at' => $comparison[$i]['at'] ?? null,
        ], $current, array_keys($current))];
    }

    /** Portable timezone-aware SQL buckets; bounded to 91 daily or 54 weekly groups.
     * @return list<array{at: string, sales: float}>
     */
    private function buckets(Builder $orders, CarbonImmutable $start, CarbonImmutable $end, string $step): array
    {
        $cases = [];
        $bindings = [];
        $points = [];
        for ($cursor = $start; $cursor->lessThan($end); $cursor = $next) {
            $next = match ($step) {
                'hour' => $cursor->addHour(), 'week' => $cursor->addWeek(), default => $cursor->addDay()
            };
            $cases[] = 'WHEN fulfilled_at >= ? AND fulfilled_at < ? THEN ?';
            $bindings[] = $this->periods->storageDate($cursor);
            $bindings[] = $this->periods->storageDate($next->min($end));
            $bindings[] = count($points);
            $points[] = ['at' => $cursor->toIso8601String(), 'sales' => 0.0];
        }
        if ($points === []) {
            return [];
        }
        $rows = (clone $orders)->selectRaw('CASE ' . implode(' ', $cases) . ' END as bucket, SUM(total - tax_amount) as sales', $bindings)->groupBy('bucket')->get();
        foreach ($rows as $row) {
            if ($row->bucket !== null && isset($points[(int) $row->bucket])) {
                $index = (int) $row->bucket;
                $points[$index] = ['at' => $points[$index]['at'], 'sales' => $this->money($row->sales)];
            }
        }

        return $points;
    }

    /** @param list<int> $storeIds
     * @param  ReportPeriod  $period
     * @return array<string, mixed>
     */
    private function inventory(array $storeIds, array $period): array
    {
        $positions = $this->alerts->storePositions($storeIds, $period['now']->toDateString());
        // Aggregate the derived positions, not quantities pooled across stores.
        $summary = DB::query()->fromSub($positions, 'positions')
            ->selectRaw('SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as stockouts, SUM(CASE WHEN stock > 0 AND stock < minimum_stock_level THEN 1 ELSE 0 END) as low_stock')->first();
        $low = (clone $positions)->where(fn ($q) => $q->where('position.stock', '<=', 0)->orWhereColumn('position.stock', '<', 'variant.minimum_stock_level'))
            ->orderBy('position.stock')->orderBy('variant.id')->orderBy('position.store_id')->limit(5)->get();
        $today = $period['now']->toDateString();
        $days = max(0, (int) Setting::get('expiry_alert_days', 30));
        $batches = DB::table('batches as b')->join('product_variants as v', 'v.id', '=', 'b.product_variant_id')
            ->join('products as p', 'p.id', '=', 'v.product_id')->join('stores as s', 's.id', '=', 'b.store_id')
            ->whereIn('b.store_id', $storeIds)->where('b.status', 'active')->where('b.remaining_quantity', '>', 0)
            ->whereNotNull('b.expiry_date')->whereNull('s.deleted_at')->where('s.status', 'active');
        $expired = (clone $batches)->where('b.expiry_date', '<', $today)->count();
        $expiring = $days > 0 ? (clone $batches)->whereBetween('b.expiry_date', [$today, $period['now']->addDays($days)->toDateString()])->count() : 0;
        $expiry = (clone $batches)->where('b.expiry_date', '<=', $days > 0 ? $period['now']->addDays($days)->toDateString() : $period['now']->subDay()->toDateString())
            ->orderBy('b.expiry_date')->orderBy('b.id')->limit(4)
            ->get(['b.id as batch_id', 'b.product_variant_id as variant_id', 'p.name as product_name', 'b.store_id', 's.name as store_name', 'b.remaining_quantity as quantity', 'b.expiry_date']);

        return ['stockouts' => (int) ($summary->stockouts ?? 0), 'low_stock' => (int) ($summary->low_stock ?? 0),
            'expired' => $expired, 'expiring' => $expiring, 'expiry_days' => $days,
            'stock_items' => $low->map(fn (stdClass $r): array => ['variant_id' => (int) $r->id, 'name' => $r->product_name,
                'identifier' => $r->identifier, 'store_id' => (int) $r->store_id, 'store_name' => $r->store_name,
                'quantity' => (int) $r->stock, 'minimum' => $r->minimum_stock_level === null ? null : (int) $r->minimum_stock_level])->all(),
            'expiry_items' => $expiry->map(fn (stdClass $r): array => (array) $r)->all()];
    }

    /** @param list<int> $storeIds
     * @param  ReportPeriod  $period
     * @return array<string, mixed>
     */
    private function collections(User $user, array $storeIds, array $period): array
    {
        $rows = DB::table('sales_order_payments as p')->join('sales_orders as o', 'o.id', '=', 'p.sales_order_id')
            ->whereIn('o.store_id', $storeIds)
            ->when(! $user->can(PermissionsEnum::SALES_VIEW_ALL->value), fn ($q) => $q->where('o.user_id', $user->id))
            ->where('p.created_at', '>=', $this->periods->storageDate($period['start']))
            ->where('p.created_at', '<', $this->periods->storageDate($period['end']))
            ->groupBy('p.payment_method')->select('p.payment_method')->selectRaw('SUM(p.amount) as amount')->get();

        return ['total' => $this->money($rows->sum('amount')), 'methods' => $rows->map(fn (stdClass $r): array => [
            'method' => $r->payment_method, 'amount' => $this->money($r->amount),
        ])->all()];
    }

    /** @param list<int> $storeIds
     * @param  ReportPeriod  $period
     * @return array<string, mixed>
     */
    private function shifts(array $storeIds, array $period): array
    {
        $base = DB::table('cash_register_shifts as shift')->join('cash_registers as r', 'r.id', '=', 'shift.cash_register_id')->whereIn('r.store_id', $storeIds);
        $closed = (clone $base)->whereIn('shift.status', ['closed', 'forced_close'])
            ->where('shift.closed_at', '>=', $this->periods->storageDate($period['start']))->where('shift.closed_at', '<', $this->periods->storageDate($period['end']))
            ->selectRaw('COUNT(*) as closed_count, SUM(CASE WHEN difference < 0 THEN -difference ELSE 0 END) as shortages, SUM(CASE WHEN difference > 0 THEN difference ELSE 0 END) as overages')->first();
        $open = (clone $base)->where('shift.status', 'open');
        $openCount = (clone $open)->count();
        $cash = DB::table('sales_order_payments')->where('payment_method', 'cash')->select('cash_register_shift_id')->selectRaw('SUM(amount) as cash')->groupBy('cash_register_shift_id');
        $movements = DB::table('cash_register_movements')->select('cash_register_shift_id')->selectRaw("SUM(CASE WHEN type = 'cash_in' THEN amount ELSE -amount END) as net_movements")->groupBy('cash_register_shift_id');
        $rows = $open->join('stores as s', 's.id', '=', 'r.store_id')->leftJoin('users as u', 'u.id', '=', 'shift.user_id')
            ->leftJoinSub($cash, 'cash', 'cash.cash_register_shift_id', '=', 'shift.id')->leftJoinSub($movements, 'movement', 'movement.cash_register_shift_id', '=', 'shift.id')
            ->select('shift.id', 'r.name as register_name', 's.name as store_name', 'u.first_name', 'u.last_name', 'shift.opened_at')
            ->selectRaw('shift.opening_balance + COALESCE(cash.cash, 0) + COALESCE(movement.net_movements, 0) as expected_cash')
            ->orderBy('shift.opened_at')->limit(5)->get();

        return ['open_count' => $openCount, 'closed_count' => (int) ($closed->closed_count ?? 0),
            'shortages' => $this->money($closed->shortages ?? 0), 'overages' => $this->money($closed->overages ?? 0),
            'open' => $rows->map(fn (stdClass $r): array => ['id' => (int) $r->id, 'register_name' => $r->register_name,
                'store_name' => $r->store_name, 'cashier' => mb_trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')),
                'expected_cash' => $this->money($r->expected_cash), 'opened_at' => CarbonImmutable::parse($r->opened_at, config('app.timezone'))->toISOString()])->all()];
    }

    /** @param list<int> $storeIds
     * @return array<string, mixed>
     */
    private function storePerformance(User $user, array $storeIds, Builder $orders, bool $profit, int $page): array
    {
        $stores = $this->scope->stores($user)->whereIn('id', $storeIds)->values();
        $totals = (clone $orders)->select('store_id')->selectRaw('COUNT(*) as orders, SUM(total - tax_amount) as sales')->groupBy('store_id')->get()->keyBy('store_id');
        $costs = $profit ? $this->lineTotals($this->lines($orders))->addSelect('o.store_id')->groupBy('o.store_id')->get()->keyBy('store_id') : collect();
        $lastPage = max(1, (int) ceil($stores->count() / 10));
        $page = min($page, $lastPage);

        return ['data' => $stores->slice(($page - 1) * 10, 10)->map(function ($store) use ($totals, $costs, $profit): array {
            $row = $totals->get($store->id);
            $net = $this->money($row->sales ?? 0);
            $count = (int) ($row->orders ?? 0);

            return ['id' => $store->id, 'name' => $store->name, 'code' => $store->code, 'net_sales' => $net,
                'order_count' => $count, 'profitability' => $profit ? $this->profitability($costs->get($store->id), $count, $net) : null];
        })->values()->all(), 'meta' => ['current_page' => $page, 'last_page' => $lastPage, 'per_page' => 10, 'total' => $stores->count()]];
    }

    private function money(mixed $value): float
    {
        return BigDecimal::of((string) $value)->toScale(2, RoundingMode::HalfUp)->toFloat();
    }
}
