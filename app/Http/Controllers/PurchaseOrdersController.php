<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\PurchaseOrders\CancelPurchaseOrderRequest;
use App\Http\Requests\PurchaseOrders\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrders\TransitionPurchaseOrderRequest;
use App\Http\Requests\PurchaseOrders\UpdatePurchaseOrderRequest;
use App\Http\Resources\PurchaseOrder\PurchaseOrderCollection;
use App\Models\Catalog;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Services\PurchaseOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use RuntimeException;

final class PurchaseOrdersController extends Controller
{
    private readonly PurchaseOrderService $poService;

    public function __construct(PurchaseOrderService $poService)
    {
        $this->poService = $poService;
    }

    public function index(Request $request): InertiaResponse
    {
        $this->authorize(PermissionsEnum::PURCHASE_ORDERS_VIEW);

        $purchaseOrders = $this->poService->list(
            filters: [
                'status' => $request->string('status', '')->toString() ?: null,
                'vendor_id' => $request->integer('vendor_id') ?: null,
                'from' => $request->string('from', '')->toString() ?: null,
                'to' => $request->string('to', '')->toString() ?: null,
            ],
            perPage: $request->integer('per_page', 25),
        );

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => new PurchaseOrderCollection($purchaseOrders),
            'filters' => [
                'status' => $request->string('status', '')->toString(),
                'vendor_id' => $request->integer('vendor_id') ?: null,
                'from' => $request->string('from', '')->toString(),
                'to' => $request->string('to', '')->toString(),
            ],
            'vendors' => Vendor::query()
                ->orderBy('fullname')
                ->where('status', 'active')
                ->get(['id', 'fullname', 'email', 'phone', 'address', 'details', 'additional_contacts']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::PURCHASE_ORDERS_CREATE);

        return Inertia::render('PurchaseOrders/Create/Index', [
            'vendors' => Vendor::query()
                ->orderBy('fullname')
                ->where('status', 'active')
                ->get(['id', 'fullname', 'email', 'phone', 'address', 'details', 'additional_contacts']),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        try {
            $po = $this->poService->create(
                $request->validated(),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('purchase-orders.show', $po->id)
            ->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder): InertiaResponse
    {
        $this->authorize(PermissionsEnum::PURCHASE_ORDERS_VIEW);

        $purchaseOrder->load(['vendor', 'user', 'lineItems.productVariant.product.measurementUnit', 'receptionOrders.vendor', 'receptionOrders.store', 'receptionOrders.user']);

        $catalogEntries = Catalog::query()
            ->where('vendor_id', $purchaseOrder->vendor_id)
            ->whereIn('product_variant_id', $purchaseOrder->lineItems->pluck('product_variant_id'))
            ->with(['unit'])
            ->get()
            ->keyBy('product_variant_id');

        $purchaseOrder->lineItems->each(fn ($item) => $item->setRelation(
            'catalogEntry',
            $catalogEntries->get($item->product_variant_id),
        ));

        return Inertia::render('PurchaseOrders/Show/Index', [
            'purchaseOrder' => $purchaseOrder,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): InertiaResponse
    {
        $this->authorize(PermissionsEnum::PURCHASE_ORDERS_EDIT);

        $purchaseOrder->load(['vendor', 'lineItems.productVariant.product.measurementUnit']);

        $catalogEntries = Catalog::query()
            ->where('vendor_id', $purchaseOrder->vendor_id)
            ->whereIn('product_variant_id', $purchaseOrder->lineItems->pluck('product_variant_id'))
            ->with(['unit'])
            ->get()
            ->keyBy('product_variant_id');

        $purchaseOrder->lineItems->each(fn ($item) => $item->setRelation(
            'catalogEntry',
            $catalogEntries->get($item->product_variant_id),
        ));

        return Inertia::render('PurchaseOrders/Edit/Index', [
            'purchaseOrder' => $purchaseOrder,
            'vendors' => Vendor::query()
                ->orderBy('fullname')
                ->where('status', 'active')
                ->get(['id', 'fullname', 'email', 'phone', 'address', 'details', 'additional_contacts']),
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        try {
            $this->poService->update(
                $purchaseOrder,
                $request->validated(),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['items' => $e->getMessage()]);
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder->id)
            ->with('success', 'Purchase order updated successfully.');
    }

    public function submit(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorize(PermissionsEnum::PURCHASE_ORDERS_EDIT);

        try {
            $this->poService->transitionStatus(
                $purchaseOrder,
                'awaiting_approval',
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order submitted for approval.');
    }

    public function approve(TransitionPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        try {
            $this->poService->transitionStatus(
                $purchaseOrder,
                $request->string('status')->toString(),
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order status updated successfully.');
    }

    public function send(TransitionPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        try {
            $this->poService->transitionStatus(
                $purchaseOrder,
                'sent',
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order marked as sent.');
    }

    public function pay(TransitionPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        try {
            $this->poService->transitionStatus(
                $purchaseOrder,
                'paid',
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order marked as paid.');
    }

    public function cancel(CancelPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        try {
            $this->poService->cancel(
                $purchaseOrder,
                $request->string('reason', '')->toString() ?: null,
                $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            redirect()->back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Purchase order cancelled.');
    }
}
