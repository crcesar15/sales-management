<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\SalesOrders\StoreSalesOrderRequest;
use App\Http\Requests\SalesOrders\TransitionStatusRequest;
use App\Http\Requests\SalesOrders\UpdateSalesOrderRequest;
use App\Http\Resources\SalesOrder\SalesOrderCollection;
use App\Http\Resources\SalesOrder\SalesOrderResource;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use RuntimeException;

final class SalesOrderController extends Controller
{
    public function __construct(
        private readonly SalesOrderService $salesOrderService,
    ) {}

    public function index(Request $request): InertiaResponse
    {
        $this->authorize(PermissionsEnum::SALES_VIEW);

        $actor = $request->user() ?? throw new RuntimeException('Unauthenticated.');

        $orders = $this->salesOrderService->list(
            filters: [
                'search' => $request->string('search', '')->toString() ?: null,
                'status' => $request->string('status', '')->toString() ?: null,
                'from' => $request->string('from', '')->toString() ?: null,
                'to' => $request->string('to', '')->toString() ?: null,
            ],
            actor: $actor,
            perPage: $request->integer('per_page', 20),
        );

        return Inertia::render('SalesOrders/Index', [
            'orders' => new SalesOrderCollection($orders),
            'filters' => [
                'search' => $request->string('search', '')->toString(),
                'status' => $request->string('status', '')->toString(),
                'from' => $request->string('from', '')->toString(),
                'to' => $request->string('to', '')->toString(),
            ],
            'canViewAll' => $actor->can(PermissionsEnum::SALES_VIEW_ALL->value),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::SALES_MANAGE);

        return Inertia::render('SalesOrders/Create/Index', [
            'customers' => Customer::query()
                ->orderBy('first_name')
                ->where('status', 'active')
                ->get(['id', 'first_name', 'last_name', 'email', 'phone', 'tax_id']),
        ]);
    }

    public function store(StoreSalesOrderRequest $request): RedirectResponse
    {
        try {
            $order = $this->salesOrderService->create(
                $request->validated(),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('sales-orders.show', $order->id)
            ->with('success', 'Sales order created successfully.');
    }

    public function show(SalesOrder $salesOrder): InertiaResponse
    {
        $this->authorize(PermissionsEnum::SALES_VIEW);

        $salesOrder->load([
            'customer',
            'user',
            'store',
            'cashRegisterShift.register',
            'items.productVariant.product',
            'items.saleUnit',
            'payments',
        ]);

        return Inertia::render('SalesOrders/Show/Index', [
            'order' => (new SalesOrderResource($salesOrder))->resolve(),
        ]);
    }

    public function edit(SalesOrder $salesOrder): InertiaResponse|RedirectResponse
    {
        $this->authorize(PermissionsEnum::SALES_MANAGE);

        if ($salesOrder->status->value !== 'draft') {
            return redirect()->route('sales-orders.show', $salesOrder->id)
                ->with('error', 'Only draft orders can be edited.');
        }

        $salesOrder->load([
            'customer',
            'items.productVariant.product',
            'items.saleUnit',
            'payments',
        ]);

        return Inertia::render('SalesOrders/Edit/Index', [
            'order' => (new SalesOrderResource($salesOrder))->resolve(),
            'customers' => Customer::query()
                ->orderBy('first_name')
                ->where('status', 'active')
                ->get(['id', 'first_name', 'last_name', 'email', 'phone', 'tax_id']),
        ]);
    }

    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $this->salesOrderService->update(
                $salesOrder,
                $request->validated(),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('sales-orders.show', $salesOrder->id)
            ->with('success', 'Sales order updated successfully.');
    }

    public function transitionStatus(TransitionStatusRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $this->salesOrderService->transitionStatus(
                $salesOrder,
                (string) $request->input('status'),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Order status updated successfully.');
    }
}
