<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\ReceptionOrders\CancelReceptionOrderRequest;
use App\Http\Requests\ReceptionOrders\CompleteReceptionOrderRequest;
use App\Http\Requests\ReceptionOrders\StoreReceptionOrderRequest;
use App\Http\Requests\ReceptionOrders\UpdateReceptionOrderRequest;
use App\Http\Resources\ReceptionOrder\ReceptionOrderCollection;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderProduct;
use App\Models\ReceptionOrder;
use App\Models\Store;
use App\Models\Vendor;
use App\Services\ReceptionOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use RuntimeException;

final class ReceptionOrderController extends Controller
{
    private readonly ReceptionOrderService $receptionService;

    public function __construct(ReceptionOrderService $receptionService)
    {
        $this->receptionService = $receptionService;
    }

    public function index(Request $request): InertiaResponse
    {
        $this->authorize(PermissionsEnum::RECEPTION_ORDERS_VIEW);

        $receptionOrders = $this->receptionService->list(
            filters: [
                'status' => $request->string('status', '')->toString() ?: null,
                'purchase_order_id' => $request->integer('purchase_order_id') ?: null,
                'vendor_id' => $request->integer('vendor_id') ?: null,
                'store_id' => $request->integer('store_id') ?: null,
                'from' => $request->string('from', '')->toString() ?: null,
                'to' => $request->string('to', '')->toString() ?: null,
            ],
            perPage: $request->integer('per_page', 25),
        );

        return Inertia::render('ReceptionOrders/Index', [
            'receptionOrders' => new ReceptionOrderCollection($receptionOrders),
            'filters' => [
                'status' => $request->string('status', '')->toString(),
                'purchase_order_id' => $request->integer('purchase_order_id') ?: null,
                'vendor_id' => $request->integer('vendor_id') ?: null,
                'store_id' => $request->integer('store_id') ?: null,
                'from' => $request->string('from', '')->toString(),
                'to' => $request->string('to', '')->toString(),
            ],
            'vendors' => Vendor::query()
                ->orderBy('fullname')
                ->where('status', 'active')
                ->get(['id', 'fullname']),
            'stores' => Store::query()
                ->orderBy('name')
                ->where('status', 'active')
                ->get(['id', 'name']),
        ]);
    }

    public function create(Request $request): InertiaResponse
    {
        $this->authorize(PermissionsEnum::RECEPTION_ORDERS_CREATE);

        $purchaseOrders = PurchaseOrder::query()
            ->whereIn('status', ['sent', 'partially_received'])
            ->with(['vendor', 'lineItems.productVariant.product.measurementUnit', 'lineItems.catalog.unit', 'receptionOrders.lineItems' => fn ($q) => $q->whereHas('receptionOrder', fn ($q) => $q->where('status', '!=', 'cancelled'))])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (PurchaseOrder $po) {
                $claimedQuantities = [];
                foreach ($po->receptionOrders as $ro) {
                    foreach ($ro->lineItems as $item) {
                        $poItemId = (int) $item->purchase_order_item_id;
                        $claimedQuantities[$poItemId] = bcadd((string) ($claimedQuantities[$poItemId] ?? '0'), (string) $item->quantity, 4);
                    }
                }

                $po->lineItems->each(function (PurchaseOrderProduct $item) use ($claimedQuantities) {
                    $ordered = (float) $item->quantity;
                    $claimed = (float) ($claimedQuantities[$item->id] ?? 0);
                    $item->setAttribute('remaining_quantity', (string) ($ordered - $claimed));
                });

                return $po;
            });

        return Inertia::render('ReceptionOrders/Create/Index', [
            'purchaseOrders' => $purchaseOrders,
            'stores' => Store::query()
                ->orderBy('name')
                ->where('status', 'active')
                ->get(['id', 'name']),
        ]);
    }

    public function store(StoreReceptionOrderRequest $request): RedirectResponse
    {
        try {
            $receptionOrder = $this->receptionService->create(
                $request->validated(),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('reception-orders.show', $receptionOrder->id)
            ->with('success', 'Reception order created successfully.');
    }

    public function show(ReceptionOrder $receptionOrder): InertiaResponse
    {
        $this->authorize(PermissionsEnum::RECEPTION_ORDERS_VIEW);

        $receptionOrder->load([
            'purchaseOrder.vendor',
            'vendor',
            'store',
            'user',
            'lineItems.productVariant.product.measurementUnit',
        ]);

        $poLineItemIds = $receptionOrder->lineItems->pluck('purchase_order_item_id')->filter()->unique()->toArray();
        $poLineItems = PurchaseOrderProduct::with('catalog.unit')
            ->whereIn('id', $poLineItemIds)->get()->keyBy('id');

        $receptionOrder->lineItems->each(fn ($item) => $item->setRelation(
            'catalogEntry',
            $poLineItems->get($item->purchase_order_item_id)?->catalog,
        ));

        return Inertia::render('ReceptionOrders/Show/Index', [
            'receptionOrder' => $receptionOrder,
        ]);
    }

    public function edit(ReceptionOrder $receptionOrder): InertiaResponse|RedirectResponse
    {
        $this->authorize(PermissionsEnum::RECEPTION_ORDERS_EDIT);

        if ($receptionOrder->status !== 'pending') {
            return redirect()->route('reception-orders.show', $receptionOrder->id)
                ->withErrors(['status' => 'Only pending reception orders can be edited.']);
        }

        $receptionOrder->load([
            'purchaseOrder.lineItems.productVariant.product',
            'purchaseOrder.lineItems.catalog.unit',
            'purchaseOrder.receptionOrders.lineItems' => fn ($q) => $q->whereHas('receptionOrder', fn ($q) => $q->where('status', '!=', 'cancelled')->where('id', '!=', $receptionOrder->id)),
            'lineItems.productVariant.product.measurementUnit',
        ]);

        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $receptionOrder->purchaseOrder;

        $claimedQuantities = [];
        foreach ($purchaseOrder->receptionOrders as $ro) {
            foreach ($ro->lineItems as $item) {
                $poItemId = (int) $item->purchase_order_item_id;
                $claimedQuantities[$poItemId] = bcadd((string) ($claimedQuantities[$poItemId] ?? '0'), (string) $item->quantity, 4);
            }
        }

        $purchaseOrder->lineItems->each(function (PurchaseOrderProduct $item) use ($claimedQuantities) {
            $ordered = (float) $item->quantity;
            $claimed = (float) ($claimedQuantities[$item->id] ?? 0);
            $item->setAttribute('remaining_quantity', (string) ($ordered - $claimed));
        });

        $poLineItemIds = $receptionOrder->lineItems->pluck('purchase_order_item_id')->filter()->unique()->toArray();
        $poLineItems = PurchaseOrderProduct::with('catalog.unit')
            ->whereIn('id', $poLineItemIds)->get()->keyBy('id');

        $receptionOrder->lineItems->each(fn ($item) => $item->setRelation(
            'catalogEntry',
            $poLineItems->get($item->purchase_order_item_id)?->catalog,
        ));

        return Inertia::render('ReceptionOrders/Edit/Index', [
            'receptionOrder' => $receptionOrder,
            'stores' => Store::query()
                ->orderBy('name')
                ->where('status', 'active')
                ->get(['id', 'name']),
        ]);
    }

    public function update(UpdateReceptionOrderRequest $request, ReceptionOrder $receptionOrder): RedirectResponse
    {
        try {
            $this->receptionService->update(
                $receptionOrder,
                $request->validated(),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('reception-orders.show', $receptionOrder->id)
            ->with('success', 'Reception order updated successfully.');
    }

    public function complete(CompleteReceptionOrderRequest $request, ReceptionOrder $receptionOrder): RedirectResponse
    {
        try {
            $this->receptionService->complete(
                $receptionOrder,
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('reception-orders.show', $receptionOrder->id)
            ->with('success', 'Reception order completed successfully.');
    }

    public function cancel(CancelReceptionOrderRequest $request, ReceptionOrder $receptionOrder): RedirectResponse
    {
        try {
            $this->receptionService->cancel(
                $receptionOrder,
                $request->string('reason', '')->toString() ?: null,
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('reception-orders')
            ->with('success', 'Reception order cancelled.');
    }
}
