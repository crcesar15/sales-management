<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProductVariant;

final class PurchasePriceHistoryService
{
    /** @return array<string, mixed> */
    public function history(ProductVariant $variant): array
    {
        $entries = $variant->replacementCostEvents()->whereNull('invalidated_at')->where('source', '!=', 'legacy')->latest('id')->limit(100)->get();
        $history = $entries->map(fn ($event): array => [
            'date' => $event->created_at?->toISOString(), 'price' => (float) $event->cost,
            'po_id' => $event->purchase_order_id, 'vendor_name' => $event->supplier_name,
            'source' => $event->source, 'conversion_factor' => $event->conversion_factor,
        ]);
        $prices = $history->pluck('price');

        return ['latest_purchase_price' => $prices->first(), 'latest_po_date' => $history->first()['date'] ?? null, 'latest_vendor_name' => $history->first()['vendor_name'] ?? null, 'history' => $history->all(), 'basis' => 'normal_replacement_cost_per_base_unit', 'stats' => ['latest' => $prices->first(), 'average' => null, 'highest' => $prices->max(), 'lowest' => $prices->min()]];
    }
}
