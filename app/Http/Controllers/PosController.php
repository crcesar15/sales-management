<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Pos\CompletePosSaleRequest;
use App\Http\Requests\Pos\DiscardPosSaleRequest;
use App\Http\Requests\Pos\StorePosSaleRequest;
use App\Http\Requests\Pos\UpdatePosSaleRequest;
use App\Http\Resources\SalesOrder\SalesOrderResource;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use RuntimeException;

final class PosController extends Controller
{
    public function __construct(private readonly SalesOrderService $salesOrderService) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS);

        $draft = $this->salesOrderService->getCurrentPosDraft(
            $request->user() ?? throw new RuntimeException('Unauthenticated.'),
        );

        return Inertia::render('Pos/Index', [
            'draft' => $draft === null ? null : (new SalesOrderResource($draft))->resolve(),
        ]);
    }

    public function store(StorePosSaleRequest $request): RedirectResponse
    {
        try {
            $order = $this->salesOrderService->createPosDraft(
                $request->validated(),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()->route('pos.sales.payment', $order);
    }

    public function edit(Request $request, SalesOrder $salesOrder): InertiaResponse|RedirectResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS);

        try {
            $order = $this->salesOrderService->getPosDraft(
                $salesOrder,
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->route('pos')->with('error', $e->getMessage());
        }

        return Inertia::render('Pos/Index', [
            'draft' => (new SalesOrderResource($order))->resolve(),
        ]);
    }

    public function update(UpdatePosSaleRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $order = $this->salesOrderService->updatePosDraft(
                $salesOrder,
                $request->validated(),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()->route('pos.sales.payment', $order);
    }

    public function payment(Request $request, SalesOrder $salesOrder): InertiaResponse|RedirectResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS);

        try {
            $order = $this->salesOrderService->getPosDraft(
                $salesOrder,
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->route('pos')->with('error', $e->getMessage());
        }

        return Inertia::render('Pos/Payment/Index', [
            'order' => (new SalesOrderResource($order))->resolve(),
        ]);
    }

    public function complete(CompletePosSaleRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $order = $this->salesOrderService->completePosSale(
                $salesOrder,
                $request->validated(),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()->route('pos.sales.receipt', $order)->with('success', 'Sale completed successfully.');
    }

    public function discard(DiscardPosSaleRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $this->salesOrderService->discardPosDraft(
                $salesOrder,
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()->route('pos')->with('success', 'Sale draft discarded.');
    }

    public function receipt(Request $request, SalesOrder $salesOrder): InertiaResponse|RedirectResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS);

        try {
            $order = $this->salesOrderService->getPosReceipt(
                $salesOrder,
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->route('pos')->with('error', $e->getMessage());
        }

        return Inertia::render('Pos/Receipt/Index', [
            'order' => (new SalesOrderResource($order))->resolve(),
        ]);
    }
}
