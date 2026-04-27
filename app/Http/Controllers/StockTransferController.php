<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Inventory\CancelTransferRequest;
use App\Http\Requests\Inventory\CreateTransferRequest;
use App\Http\Requests\Inventory\UpdateTransferStatusRequest;
use App\Http\Resources\StockTransfer\StockTransferCollection;
use App\Http\Resources\StockTransfer\StockTransferResource;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Services\StockTransferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use RuntimeException;

final class StockTransferController extends Controller
{
    private readonly StockTransferService $transferService;

    public function __construct(StockTransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    public function index(Request $request): InertiaResponse
    {
        $this->authorize(PermissionsEnum::STOCK_TRANSFERS_VIEW);

        $user = $request->user() ?? throw new RuntimeException('Unauthenticated.');

        $transfers = $this->transferService->list(
            filters: [
                'status' => $request->string('status', '')->toString() ?: null,
                'from_store_id' => $request->integer('from_store_id') ?: null,
                'to_store_id' => $request->integer('to_store_id') ?: null,
            ],
            perPage: $request->integer('per_page', 25),
            user: $user,
        );

        return Inertia::render('StockTransfers/Index', [
            'transfers' => new StockTransferCollection($transfers),
            'filters' => [
                'status' => $request->string('status', '')->toString(),
                'from_store_id' => $request->integer('from_store_id') ?: null,
                'to_store_id' => $request->integer('to_store_id') ?: null,
            ],
            'stores' => Store::query()->where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::STOCK_TRANSFERS_CREATE);

        return Inertia::render('StockTransfers/Create/Index', [
            'stores' => Store::query()->where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(CreateTransferRequest $request): RedirectResponse
    {
        $transfer = $this->transferService->createTransfer(
            $request->validated(),
            $request->user() ?? throw new RuntimeException('Unauthenticated.'),
        );

        return redirect()->route('stock-transfers.show', $transfer->id)
            ->with('success', 'Transfer created successfully.');
    }

    public function show(StockTransfer $stockTransfer): InertiaResponse
    {
        $this->authorize(PermissionsEnum::STOCK_TRANSFERS_VIEW);

        $stockTransfer->load(['fromStore', 'toStore', 'requestedBy', 'items.productVariant.product.brand']);

        return Inertia::render('StockTransfers/Show/Index', [
            'transfer' => (new StockTransferResource($stockTransfer))->resolve(),
        ]);
    }

    public function updateStatus(UpdateTransferStatusRequest $request, StockTransfer $stockTransfer): RedirectResponse
    {
        try {
            $this->transferService->transitionStatus(
                $stockTransfer,
                $request->string('status')->toString(),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
                $request->array('items') ?: null,
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Transfer status updated successfully.');
    }

    public function cancel(CancelTransferRequest $request, StockTransfer $stockTransfer): RedirectResponse
    {
        try {
            $this->transferService->cancelTransfer(
                $stockTransfer,
                $request->string('reason', '')->toString() ?: null,
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['transfer' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Transfer cancelled successfully.');
    }
}
