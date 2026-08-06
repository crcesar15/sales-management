<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\SalesOrders\CancelSalesOrderRequest;
use App\Http\Requests\SalesOrders\FulfillSalesOrderRequest;
use App\Http\Requests\SalesOrders\PaySalesOrderRequest;
use App\Http\Requests\SalesOrders\StoreSalesOrderRequest;
use App\Http\Requests\SalesOrders\UpdateSalesOrderCheckoutRequest;
use App\Http\Requests\SalesOrders\UpdateSalesOrderRequest;
use App\Http\Requests\SalesOrders\ValidateSalesOrderRequest;
use App\Http\Resources\SalesOrder\SalesOrderCollection;
use App\Http\Resources\SalesOrder\SalesOrderResource;
use App\Models\SalesOrder;
use App\Models\Store;
use App\Services\SalesOrderService;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function create(Request $request): InertiaResponse
    {
        $this->authorize(PermissionsEnum::SALES_MANAGE);

        $actor = $request->user() ?? throw new RuntimeException('Unauthenticated.');

        $stores = Store::query()
            ->where('status', 'active')
            ->whereHas('users', fn ($q) => $q->where('users.id', $actor->id))
            ->get(['id', 'name', 'code']);

        return Inertia::render('SalesOrders/Create/Index', [
            'stores' => $stores,
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

        return redirect()->route('sales-orders.checkout', $order->id)
            ->with('success', 'Sales order created successfully.');
    }

    public function show(SalesOrder $salesOrder): InertiaResponse|RedirectResponse
    {
        $this->authorize(PermissionsEnum::SALES_VIEW);

        if (! in_array($salesOrder->status->value, ['completed', 'cancelled'], true)) {
            return redirect()->route('sales-orders.edit', $salesOrder);
        }

        $salesOrder->load([
            'customer',
            'user',
            'store',
            'cashRegisterShift.register',
            'items.productVariant.product.brand',
            'items.saleUnit',
            'items.stockAllocations.batch',
            'payments',
        ]);

        return Inertia::render('SalesOrders/Show/Index', [
            'order' => (new SalesOrderResource($salesOrder))->resolve(),
        ]);
    }

    public function edit(SalesOrder $salesOrder): InertiaResponse|RedirectResponse
    {
        $this->authorize(PermissionsEnum::SALES_MANAGE);

        if (in_array($salesOrder->status->value, ['completed', 'cancelled'], true)) {
            return redirect()->route('sales-orders.show', $salesOrder->id)
                ->with('error', 'Completed and cancelled orders are read-only.');
        }

        $salesOrder->load([
            'customer',
            'user',
            'store',
            'items.productVariant.product.brand',
            'items.productVariant.product.measurementUnit',
            'items.productVariant.values',
            'items.productVariant.batches' => fn (HasMany $query): HasMany => $query
                ->where('status', 'active')
                ->where('store_id', $salesOrder->store_id),
            'items.saleUnit',
            'items.stockAllocations.batch',
            'payments',
        ]);

        return Inertia::render('SalesOrders/Edit/Index', [
            'order' => (new SalesOrderResource($salesOrder))->resolve(),
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

        return redirect()->route('sales-orders.edit', $salesOrder->id)
            ->with('success', 'Sales order updated successfully.');
    }

    public function checkout(SalesOrder $salesOrder): InertiaResponse
    {
        $this->authorize(PermissionsEnum::SALES_MANAGE);
        $salesOrder->load(['customer', 'user', 'store', 'cashRegisterShift.register', 'items.productVariant.product.brand', 'items.saleUnit', 'payments']);

        return Inertia::render('SalesOrders/Checkout/Index', [
            'order' => (new SalesOrderResource($salesOrder))->resolve(),
        ]);
    }

    public function updateCheckout(UpdateSalesOrderCheckoutRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $this->salesOrderService->updateCheckout($salesOrder, $request->validated(), $request->user() ?? throw new RuntimeException('Unauthenticated.'));
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()->route('sales-orders.checkout', $salesOrder)->with('success', 'Checkout details updated successfully.');
    }

    public function validateOrder(ValidateSalesOrderRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $this->salesOrderService->validate($salesOrder, $request->user() ?? throw new RuntimeException('Unauthenticated.'));
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['validation' => $e->getMessage()]);
        }

        return redirect()->route('sales-orders.edit', $salesOrder)->with('success', 'Sales order validated successfully.');
    }

    public function fulfill(FulfillSalesOrderRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $order = $this->salesOrderService->fulfill($salesOrder, $request->user() ?? throw new RuntimeException('Unauthenticated.'));
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['fulfillment' => $e->getMessage()]);
        }

        return redirect()->route($order->status->value === 'completed' ? 'sales-orders.show' : 'sales-orders.edit', $order)->with('success', 'Sales order fulfilled successfully.');
    }

    public function pay(PaySalesOrderRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $order = $this->salesOrderService->pay($salesOrder, $request->validated('payments'), $request->user() ?? throw new RuntimeException('Unauthenticated.'));
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['payments' => $e->getMessage()]);
        }

        return redirect()->route($order->status->value === 'completed' ? 'sales-orders.show' : 'sales-orders.edit', $order)->with('success', 'Sales order paid successfully.');
    }

    public function cancel(CancelSalesOrderRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $this->salesOrderService->cancel($salesOrder, $request->string('reason')->toString(), $request->user() ?? throw new RuntimeException('Unauthenticated.'));
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['cancellation' => $e->getMessage()]);
        }

        return redirect()->route('sales-orders.show', $salesOrder)->with('success', 'Sales order cancelled successfully.');
    }
}
