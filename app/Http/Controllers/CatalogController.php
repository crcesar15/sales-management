<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Catalog\StoreCatalogRequest;
use App\Http\Requests\Catalog\UpdateCatalogRequest;
use App\Http\Resources\CatalogCollection;
use App\Models\Catalog;
use App\Services\CatalogService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class CatalogController extends Controller
{
    public function __construct(private readonly CatalogService $catalogService) {}

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_VIEW);

        $status = request()->string('status', 'all')->value();

        $catalog = $this->catalogService->list(
            status: $status,
            orderBy: request()->string('order_by', 'created_at')->value(),
            orderDirection: request()->string('order_direction', 'desc')->value(),
            perPage: request()->integer('per_page', 20),
            filter: request()->string('filter')->value() ?: null,
            vendorId: request()->integer('vendor_id') ?: null,
        );

        return Inertia::render('Catalog/Index', [
            'catalog' => new CatalogCollection($catalog),
            'filters' => [
                'filter' => request()->string('filter')->value() ?: null,
                'status' => $status,
                'order_by' => request()->string('order_by', 'created_at')->value(),
                'order_direction' => request()->string('order_direction', 'desc')->value(),
                'per_page' => request()->integer('per_page', 20),
                'vendor_id' => request()->integer('vendor_id') ?: null,
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_CREATE);

        return Inertia::render('Catalog/Create/Index');
    }

    public function store(StoreCatalogRequest $request): RedirectResponse
    {
        $this->catalogService->create($request->validated());

        return redirect()->route('catalog');
    }

    public function edit(Catalog $catalog): InertiaResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_EDIT);

        $catalog->load(['vendor', 'productVariant.product', 'unit']);

        return Inertia::render('Catalog/Edit/Index', [
            'catalog' => $catalog,
        ]);
    }

    public function update(UpdateCatalogRequest $request, Catalog $catalog): RedirectResponse
    {
        $this->catalogService->update($catalog, $request->validated());

        return redirect()->route('catalog');
    }

    public function destroy(Catalog $catalog): RedirectResponse
    {
        $this->authorize(PermissionsEnum::CATALOG_DELETE);

        try {
            $this->catalogService->delete($catalog);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('catalog');
    }
}
