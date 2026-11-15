<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Batch;
use App\Models\ProductVariant;
use App\Models\ReplacementCostEvent;
use App\Models\Setting;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class PriceReviewService
{
    public function __construct(private readonly PricingRecommendationService $pricing) {}

    /** @return array<string, mixed> */
    public function summary(ProductVariant $variant): array
    {
        $variant->loadMissing(['pricingCatalog.vendor', 'pricingCatalog.unit', 'replacementCostEvent', 'catalogEntries.vendor', 'catalogEntries.unit', 'activeSaleUnits', 'product.measurementUnit', 'pendingCostReviews', 'recentCostEvents']);
        $event = $variant->replacementCostEvent;
        $offer = $variant->pricingCatalog;
        $target = $variant->margin_value;
        if ($target !== null && ((float) $target < 0 || ($variant->margin_type->value === 'percent' && (float) $target >= 100))) {
            $target = null;
        }
        $outdated = $variant->reference_outdated || ($event !== null && $event->source !== 'legacy' && ($offer === null || $offer->is_outdated || $offer->status !== 'active' || $offer->vendor?->status !== 'active' || ($offer->unit_id !== null && $offer->unit?->status !== 'active')));
        $evaluation = $this->pricing->evaluate($variant->replacement_cost, (string) $variant->price, $variant->margin_type->value, $target);
        $units = $variant->activeSaleUnits->map(fn ($unit): array => [
            'id' => $unit->id, 'name' => $unit->name, 'conversion_factor' => $unit->conversion_factor,
            ...$this->pricing->evaluate($variant->replacement_cost, (string) ($unit->price ?? 0), $variant->margin_type->value, $target, $unit->conversion_factor),
        ]);
        $pending = $variant->pendingCostReviews;
        $reasons = [];
        if ($evaluation['below_cost'] || $units->contains('below_cost', true)) {
            $reasons[] = 'Below replacement cost';
        }
        if ($evaluation['below_target'] || $units->contains('below_target', true)) {
            $reasons[] = 'Below target margin';
        }
        if ($pending->contains(fn (ReplacementCostEvent $entry): bool => BigDecimal::of($entry->cost)->isGreaterThan($entry->previous_cost ?? '0'))) {
            $reasons[] = 'Cost increased';
        }
        if ($pending->contains(fn (ReplacementCostEvent $entry): bool => BigDecimal::of($entry->cost)->isLessThan($entry->previous_cost ?? '0'))) {
            $reasons[] = 'Cost decreased';
        }
        if ($outdated) {
            $reasons[] = 'Reference outdated';
        }
        if ($variant->replacement_cost === null || $event?->source === 'legacy') {
            $reasons[] = 'Select a pricing supplier';
        }
        if ($target === null) {
            $reasons[] = 'Set a target margin';
        }

        return [
            'reference_id' => $variant->replacement_cost_event_id, 'catalog_id' => $variant->pricing_catalog_id,
            'target_type' => $variant->margin_type->value, 'target_value' => $target === null ? null : (float) $target,
            'replacement_cost' => $variant->replacement_cost === null ? null : (float) $variant->replacement_cost,
            'legacy_cost' => $variant->purchase_price === null ? null : (float) $variant->purchase_price,
            'supplier_name' => $event?->supplier_name, 'source' => $event?->source,
            'effective_at' => $event?->created_at?->toISOString(), 'outdated' => $outdated,
            'previous_cost' => $event?->previous_cost === null ? null : (float) $event->previous_cost,
            'change_amount' => $event?->previous_cost === null ? null : (float) $event->cost - (float) $event->previous_cost,
            'change_percent' => $event?->previous_cost === null || (float) $event->previous_cost === 0.0 ? null : round(((float) $event->cost / (float) $event->previous_cost - 1) * 100, 2),
            'pending_review' => $pending->isNotEmpty(), 'reasons' => $reasons, 'evaluation' => $evaluation, 'sale_units' => $units->all(),
            'base_unit' => $variant->product->measurementUnit->name ?? 'Unit',
            'offers' => $variant->catalogEntries->map(fn ($entry): array => [
                'id' => $entry->id, 'vendor_id' => $entry->vendor_id, 'supplier_name' => $entry->vendor?->fullname,
                'unit_name' => $entry->unit->name ?? $variant->product->measurementUnit->name ?? 'Unit',
                'cost' => (float) $this->pricing->normalize((string) $entry->price, $entry->unit->conversion_factor ?? 1),
                'price' => (float) $entry->price, 'conversion_factor' => $entry->unit->conversion_factor ?? 1,
                'eligible' => $entry->status === 'active' && ! $entry->is_outdated && $entry->vendor?->status === 'active' && ($entry->unit_id === null || ($entry->unit?->type === 'purchase' && $entry->unit->status === 'active')),
                'is_outdated' => $entry->is_outdated,
            ])->all(),
            'history' => $variant->recentCostEvents->map(fn (ReplacementCostEvent $entry): array => [
                'id' => $entry->id, 'cost' => (float) $entry->cost, 'supplier_name' => $entry->supplier_name,
                'source' => $entry->source, 'purchase_order_id' => $entry->purchase_order_id,
                'date' => $entry->created_at?->toISOString(), 'invalidated' => $entry->invalidated_at !== null,
            ])->all(),
        ];
    }

    /** @param list<int> $storeIds
     * @return array<string, mixed>
     */
    public function actualCosts(ProductVariant $variant, array $storeIds): array
    {
        $today = now((string) Setting::get('timezone', config('app.timezone')))->toDateString();
        $query = Batch::query()->where('product_variant_id', $variant->id)->whereIn('store_id', $storeIds)->where('status', 'active')->where('remaining_quantity', '>', 0)->where(fn ($q) => $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', $today));
        $totals = (clone $query)->selectRaw('SUM(remaining_quantity) as total_quantity, SUM(CASE WHEN unit_cost IS NOT NULL THEN remaining_quantity ELSE 0 END) as known_quantity, SUM(remaining_quantity * unit_cost) as total_cost')->first();
        $known = (int) ($totals?->getAttribute('known_quantity') ?? 0);
        $total = (int) ($totals?->getAttribute('total_quantity') ?? 0);
        $latest = Batch::query()->where('product_variant_id', $variant->id)->whereIn('store_id', $storeIds)->where('cost_source', 'purchase_receipt')->latest('id')->first();
        $receipts = Batch::query()->where('product_variant_id', $variant->id)->whereIn('store_id', $storeIds)->where('cost_source', 'purchase_receipt')->whereNotNull('reception_order_id')->where('initial_quantity', '>', 0)
            ->selectRaw('reception_order_id, MAX(created_at) as received_at, SUM(initial_quantity * unit_cost) * 1.0 / SUM(initial_quantity) as received_cost, SUM(CASE WHEN unit_cost IS NULL THEN initial_quantity ELSE 0 END) as missing_cost_quantity')
            ->groupBy('reception_order_id')->orderByDesc('received_at')->limit(20)->get()->map(fn (Batch $receipt): array => [
                'reception_order_id' => $receipt->reception_order_id, 'date' => CarbonImmutable::parse((string) $receipt->getAttribute('received_at'))->toISOString(),
                'cost' => (int) $receipt->getAttribute('missing_cost_quantity') > 0 ? null : (float) $receipt->getAttribute('received_cost'),
            ])->all();

        return ['average' => $known > 0 ? (float) $totals->getAttribute('total_cost') / $known : null, 'known_quantity' => $known, 'total_quantity' => $total, 'complete' => $known === $total, 'latest_received_cost' => $latest?->unit_cost === null ? null : (float) $latest->unit_cost, 'latest_received_at' => $latest?->created_at?->toISOString(), 'receipts' => $receipts];
    }

    /** @param list<int>|null $storeIds
     * @return LengthAwarePaginator<int, array{id: int, product_name: string|null, name: string, price: float, pricing: array<string, mixed>}>
     */
    public function alerts(?array $storeIds, int $perPage = 20, ?string $reason = null, ?string $severity = null, string $reviewState = 'active'): LengthAwarePaginator
    {
        $query = ProductVariant::query()->where('status', 'active')->whereHas('product', fn ($q) => $q->where('status', 'active'))
            ->when($storeIds !== null, fn ($q) => $q->whereHas('batches', fn ($b) => $b->whereIn('store_id', $storeIds)))
            ->with(['product.measurementUnit', 'values.option', 'activeSaleUnits', 'pricingCatalog.vendor', 'pricingCatalog.unit', 'replacementCostEvent', 'catalogEntries.vendor', 'catalogEntries.unit', 'pendingCostReviews', 'recentCostEvents']);
        if ($reviewState === 'reviewed') {
            $query->whereHas('replacementCostEvents', fn ($events) => $events->whereNull('invalidated_at')->whereNotNull('reviewed_at')->whereNotNull('previous_cost'));
        }
        if ($severity === 'critical') {
            $query->where(fn ($risk) => $risk->whereRaw('price < replacement_cost')->orWhereHas('activeSaleUnits', fn ($units) => $units->whereRaw('COALESCE(price, 0) < product_variants.replacement_cost * conversion_factor')));
        } elseif ($severity === 'other') {
            $query->where(fn ($risk) => $risk->whereNull('replacement_cost')->orWhereRaw('price >= replacement_cost'))->whereDoesntHave('activeSaleUnits', fn ($units) => $units->whereRaw('COALESCE(price, 0) < product_variants.replacement_cost * conversion_factor'));
        }
        $query->where(function (Builder $q) use ($reason, $reviewState): void {
            if ($reason === null || $reason === 'pricing') {
                $q->orWhereRaw('price < replacement_cost')->orWhereRaw('margin_value IS NOT NULL AND price < CASE WHEN margin_type = ? AND margin_value < 100 THEN CEIL(replacement_cost / (1 - margin_value / 100) * 100) / 100 WHEN margin_type = ? THEN CEIL((replacement_cost + margin_value) * 100) / 100 ELSE NULL END', ['percent', 'amount'])
                    ->orWhereHas('activeSaleUnits', fn ($units) => $units->whereRaw('COALESCE(price, 0) < product_variants.replacement_cost * conversion_factor')->orWhereRaw('product_variants.margin_value IS NOT NULL AND COALESCE(price, 0) < CASE WHEN product_variants.margin_type = ? AND product_variants.margin_value < 100 THEN CEIL(product_variants.replacement_cost * conversion_factor / (1 - product_variants.margin_value / 100) * 100) / 100 WHEN product_variants.margin_type = ? THEN CEIL((product_variants.replacement_cost + product_variants.margin_value) * conversion_factor * 100) / 100 ELSE NULL END', ['percent', 'amount']));
            }
            if ($reason === null || $reason === 'changes') {
                $q->orWhereHas('replacementCostEvents', fn ($events) => $events->whereNull('invalidated_at')->when($reviewState === 'reviewed', fn ($reviewed) => $reviewed->whereNotNull('reviewed_at'), fn ($pending) => $pending->whereNull('reviewed_at'))->whereNotNull('previous_cost'));
            }
            if ($reason === null || $reason === 'setup') {
                $q->orWhereNull('replacement_cost')->orWhereNull('margin_value')->orWhere(fn ($invalid) => $invalid->where('margin_type', 'percent')->where('margin_value', '>=', 100))->orWhere('reference_outdated', true)
                    ->orWhereHas('replacementCostEvent', fn ($events) => $events->where('source', 'legacy'))
                    ->orWhere(fn ($missing) => $missing->whereNotNull('replacement_cost_event_id')->whereDoesntHave('pricingCatalog'))
                    ->orWhereHas('pricingCatalog', fn ($offers) => $offers->where('is_outdated', true)->orWhere('status', '!=', 'active')->orWhereHas('vendor', fn ($vendor) => $vendor->where('status', '!=', 'active'))->orWhereHas('unit', fn ($unit) => $unit->where('status', '!=', 'active')));
            }
        });
        $page = $query->orderByRaw("CASE WHEN price < replacement_cost OR EXISTS (SELECT 1 FROM product_variant_units AS sale_unit WHERE sale_unit.product_variant_id = product_variants.id AND sale_unit.type = 'sale' AND sale_unit.status = 'active' AND COALESCE(sale_unit.price, 0) < product_variants.replacement_cost * sale_unit.conversion_factor) THEN 0 ELSE 1 END")->orderBy('id')->paginate($perPage, ['*'], 'price_review_page')->withQueryString();

        return $page->through(fn (ProductVariant $variant): array => [
            'id' => $variant->id, 'product_name' => $variant->product?->name, 'name' => $variant->name, 'price' => (float) $variant->price,
            'pricing' => $this->summary($variant),
        ]);
    }
}
