<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AdjustmentReason;
use App\Enums\PermissionsEnum;
use App\Http\Requests\Inventory\CreateAdjustmentRequest;
use App\Http\Resources\StockAdjustment\StockAdjustmentCollection;
use App\Http\Resources\StockAdjustment\StockAdjustmentResource;
use App\Models\StockAdjustment;
use App\Models\Store;
use App\Services\StockAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use RuntimeException;

final class StockAdjustmentController extends Controller
{
    private readonly StockAdjustmentService $adjustmentService;

    public function __construct(StockAdjustmentService $adjustmentService)
    {
        $this->adjustmentService = $adjustmentService;
    }

    public function index(Request $request): InertiaResponse
    {
        $this->authorize(PermissionsEnum::STOCK_ADJUST);

        $user = $request->user() ?? throw new RuntimeException('Unauthenticated.');

        $adjustments = $this->adjustmentService->list(
            filters: [
                'store_id' => $request->integer('store_id') ?: null,
                'reason' => $request->string('reason', '')->toString() ?: null,
                'date_from' => $request->string('date_from', '')->toString() ?: null,
                'date_to' => $request->string('date_to', '')->toString() ?: null,
            ],
            perPage: $request->integer('per_page', 25),
            user: $user,
        );

        return Inertia::render('StockAdjustments/Index', [
            'adjustments' => new StockAdjustmentCollection($adjustments),
            'filters' => [
                'store_id' => $request->integer('store_id') ?: null,
                'reason' => $request->string('reason', '')->toString(),
                'date_from' => $request->string('date_from', '')->toString(),
                'date_to' => $request->string('date_to', '')->toString(),
            ],
            'stores' => Store::query()->where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::STOCK_ADJUST);

        return Inertia::render('StockAdjustments/Create/Index', [
            'stores' => Store::query()->where('status', 'active')->get(['id', 'name', 'code']),
            'reasons' => collect(AdjustmentReason::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->value,
            ]),
        ]);
    }

    public function store(CreateAdjustmentRequest $request): RedirectResponse
    {
        try {
            $adjustment = $this->adjustmentService->apply(
                $request->validated(),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['quantity_change' => $e->getMessage()]);
        }

        return redirect()->route('stock-adjustments.show', $adjustment->id)
            ->with('success', 'Stock adjustment applied successfully.');
    }

    public function show(StockAdjustment $stockAdjustment): InertiaResponse
    {
        $this->authorize('view', $stockAdjustment);

        $stockAdjustment->load(['productVariant.product.brand', 'store', 'user', 'batch']);

        return Inertia::render('StockAdjustments/Show/Index', [
            'adjustment' => (new StockAdjustmentResource($stockAdjustment))->resolve(),
        ]);
    }
}
