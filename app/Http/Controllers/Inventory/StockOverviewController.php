<?php

declare(strict_types=1);

namespace App\Http\Controllers\Inventory;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Services\StockService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class StockOverviewController extends Controller
{
    public function __construct(private readonly StockService $stockService) {}

    public function show(ProductVariant $variant): InertiaResponse
    {
        $this->authorize(PermissionsEnum::INVENTORY_VIEW);

        $breakdown = $this->stockService->getVariantStockBreakdown($variant);
        $variant = $breakdown['variant'];
        $totalQuantity = $breakdown['total_quantity'];

        return Inertia::render('Inventory/Stock/Show', [
            'stockDetail' => [
                'variant' => [
                    'id' => $variant->id,
                    'product_id' => $variant->product_id,
                    'product_name' => $variant->product?->name,
                    'brand_name' => $variant->product?->brand?->name,
                    'name' => $variant->name,
                    'identifier' => $variant->identifier,
                    'barcode' => $variant->barcode,
                    'price' => (float) $variant->price,
                    'status' => $variant->status,
                    'total_stock' => $totalQuantity,
                    'is_low_stock' => $totalQuantity <= 0,
                    'values' => $variant->values->map(fn ($v) => [
                        'id' => $v->id,
                        'value' => $v->value,
                        'option_name' => $v->option?->name,
                    ]),
                ],
                'stores' => $breakdown['stores']->map(fn ($row) => [
                    'store_id' => $row['store']?->id,
                    'store_name' => $row['store']?->name,
                    'store_code' => $row['store']?->code,
                    'quantity' => $row['quantity'],
                ]),
            ],
        ]);
    }
}
