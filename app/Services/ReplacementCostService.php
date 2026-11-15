<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Catalog;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\ReplacementCostEvent;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class ReplacementCostService
{
    public function __construct(private readonly PricingRecommendationService $pricing) {}

    public function manage(ProductVariant $variant, int $referenceId, string $action, User $actor): void
    {
        DB::transaction(function () use ($variant, $referenceId, $action, $actor): void {
            $locked = ProductVariant::query()->lockForUpdate()->whereKey($variant->id)->sole();
            if ($locked->replacement_cost_event_id !== $referenceId) {
                throw new InvalidArgumentException('The replacement cost changed. Reload the page before reviewing.');
            }
            if ($action === 'review') {
                $this->acknowledge($locked, $referenceId, $actor);
            } else {
                $this->markFreshness($locked, $action === 'outdated');
            }
        });
    }

    public function select(ProductVariant $variant, int $catalogId, User $actor): void
    {
        $offer = Catalog::query()->with(['vendor', 'unit'])->where('product_variant_id', $variant->id)->findOrFail($catalogId);
        if ($offer->status !== 'active' || $offer->is_outdated || $offer->vendor?->status !== 'active' || ($offer->unit_id !== null && ($offer->unit?->type !== 'purchase' || $offer->unit->status !== 'active'))) {
            throw new InvalidArgumentException('Select an active, current supplier offer.');
        }
        $this->record($variant, (string) $offer->price, $offer->unit->conversion_factor ?? 1, 'manual_selection', $actor, $offer);
    }

    public function publishOrder(PurchaseOrder $order, User $actor): void
    {
        $order->loadMissing(['lineItems.unit', 'vendor']);
        $grouped = $order->lineItems->groupBy('product_variant_id')->sortKeys();
        foreach ($grouped as $variantId => $lines) {
            $costs = $lines->map(fn ($line): string => $this->pricing->normalize((string) $line->price, $line->unit->conversion_factor ?? 1))->unique();
            $selected = $lines->where('pricing_reference', true);
            if ($selected->count() > 1 || ($costs->count() !== 1 && $selected->count() !== 1)) {
                throw new InvalidArgumentException('The same product has conflicting normal costs. Select one line as its pricing reference before approving.');
            }
            $line = $selected->first() ?? $lines->firstOrFail();
            if ($line->unit_id !== null && ($line->unit?->product_variant_id !== $line->product_variant_id || $line->unit->type !== 'purchase' || $line->unit->status !== 'active')) {
                throw new InvalidArgumentException('A purchase unit is no longer available.');
            }
            $variant = ProductVariant::query()->lockForUpdate()->findOrFail($variantId);
            $offer = $line->catalog_id === null ? null : Catalog::query()->with(['vendor', 'unit'])->find($line->catalog_id);
            if ($offer !== null && ($offer->product_variant_id !== $variant->id || $offer->vendor_id !== $order->vendor_id || $offer->unit_id !== $line->unit_id)) {
                throw new InvalidArgumentException('A supplier offer no longer matches the purchase order.');
            }
            $this->record($variant, (string) $line->price, $line->unit->conversion_factor ?? 1, 'purchase_order', $actor, $offer, $order, $line->id, $line->unit?->name);
        }
    }

    public function withdrawOrder(PurchaseOrder $order): void
    {
        $variantIds = ReplacementCostEvent::query()->where('purchase_order_id', $order->id)->orderBy('product_variant_id')->pluck('product_variant_id')->unique();
        foreach ($variantIds as $id) {
            $variant = ProductVariant::query()->lockForUpdate()->whereKey($id)->sole();
            $current = $variant->replacementCostEvent;
            ReplacementCostEvent::query()->where('product_variant_id', $id)->where('purchase_order_id', $order->id)->update(['invalidated_at' => now()]);
            if ($current?->purchase_order_id !== $order->id) {
                continue;
            }
            $fallback = $variant->replacementCostEvents()->whereNull('invalidated_at')->latest('id')->first();
            $variant->update([
                'replacement_cost' => $fallback?->cost, 'replacement_cost_event_id' => $fallback?->id,
                'pricing_catalog_id' => $fallback?->catalog_id, 'reference_outdated' => $fallback === null || $fallback->source === 'legacy',
            ]);
        }
    }

    public function refreshCatalog(Catalog $offer, User $actor): void
    {
        $variant = ProductVariant::query()->where('pricing_catalog_id', $offer->id)->lockForUpdate()->first();
        if ($variant === null) {
            return;
        }
        if ($variant->id !== $offer->product_variant_id) {
            $variant->update(['reference_outdated' => true, 'pricing_catalog_id' => null]);

            return;
        }
        if ($offer->status !== 'active' || $offer->is_outdated) {
            $variant->update(['reference_outdated' => true]);

            return;
        }
        $offer->load(['vendor', 'unit']);
        $this->record($variant, (string) $offer->price, $offer->unit->conversion_factor ?? 1, 'catalog', $actor, $offer);
    }

    public function acknowledge(ProductVariant $variant, int $eventId, User $actor): void
    {
        $variant->replacementCostEvents()->whereNull('invalidated_at')->whereNull('reviewed_at')->where('id', '<=', $eventId)->update(['reviewed_at' => now(), 'reviewed_by' => $actor->id]);
        activity('replacement_cost')->performedOn($variant)->causedBy($actor)->log('Cost changes reviewed');
    }

    public function markFreshness(ProductVariant $variant, bool $outdated): void
    {
        $variant->update(['reference_outdated' => $outdated]);
        if ($variant->pricing_catalog_id !== null) {
            $offer = Catalog::query()->find($variant->pricing_catalog_id);
            $offer?->update(['is_outdated' => $outdated]);
        }
    }

    private function record(ProductVariant $variant, string $price, int $factor, string $source, User $actor, ?Catalog $offer = null, ?PurchaseOrder $order = null, ?int $lineId = null, ?string $unitName = null): void
    {
        if ($lineId !== null && ReplacementCostEvent::query()->where('purchase_order_item_id', $lineId)->exists()) {
            return;
        }
        if ($variant->replacement_cost_event_id === null && $variant->purchase_price !== null) {
            $baseline = ReplacementCostEvent::create([
                'product_variant_id' => $variant->id, 'source' => 'legacy', 'unit_price' => $variant->purchase_price,
                'conversion_factor' => 1, 'cost' => $variant->purchase_price, 'reviewed_at' => now(),
            ]);
            $variant->update(['replacement_cost' => $baseline->cost, 'replacement_cost_event_id' => $baseline->id]);
        }
        $cost = $this->pricing->normalize($price, $factor);
        $previous = $variant->replacement_cost;
        $changed = $previous !== null && ! BigDecimal::of($previous)->isEqualTo($cost);
        $event = ReplacementCostEvent::create([
            'product_variant_id' => $variant->id, 'vendor_id' => $order->vendor_id ?? $offer?->vendor_id,
            'catalog_id' => $offer?->id, 'purchase_order_id' => $order?->id, 'purchase_order_item_id' => $lineId,
            'user_id' => $actor->id, 'source' => $source, 'supplier_name' => $order->vendor->fullname ?? $offer?->vendor?->fullname,
            'unit_name' => $unitName ?? $offer?->unit?->name, 'unit_price' => $price, 'conversion_factor' => $factor,
            'cost' => $cost, 'previous_cost' => $previous, 'reviewed_at' => $changed ? null : now(),
        ]);
        $variant->update([
            'replacement_cost' => $cost, 'replacement_cost_event_id' => $event->id,
            'pricing_catalog_id' => $offer?->id, 'reference_outdated' => false,
        ]);
    }
}
