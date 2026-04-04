<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Brands\StoreBrandRequest;
use App\Http\Requests\Brands\UpdateBrandRequest;
use App\Http\Resources\Brand\BrandCollection;
use App\Models\Brand;
use App\Services\BrandService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class BrandController extends Controller
{
    private readonly BrandService $brandService;

    public function __construct(BrandService $brandService)
    {
        $this->brandService = $brandService;
    }

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::BRANDS_VIEW);

        $status = request()->string('status', 'active')->value();

        $brands = $this->brandService->list(
            status: $status,
            orderBy: request()->string('order_by', 'name')->value(),
            orderDirection: request()->string('order_direction', 'asc')->value(),
            perPage: request()->integer('per_page', 20),
            filter: request()->string('filter')->value() ?: null,
        );

        return Inertia::render('Brands/Index', [
            'brands' => new BrandCollection($brands),
            'filters' => [
                'filter' => request()->string('filter')->value() ?: null,
                'status' => $status,
                'order_by' => request()->string('order_by', 'name')->value(),
                'order_direction' => request()->string('order_direction', 'asc')->value(),
                'per_page' => request()->integer('per_page', 20),
            ],
        ]);
    }

    public function store(StoreBrandRequest $request): RedirectResponse
    {
        $this->brandService->create($request->validated());

        return redirect()->route('brands');
    }

    public function update(UpdateBrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->brandService->update($brand, $request->validated());

        return redirect()->route('brands');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorize(PermissionsEnum::BRANDS_DELETE);

        try {
            $this->brandService->delete($brand);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('brands');
    }

    public function restore(Brand $brand): RedirectResponse
    {
        $this->authorize(PermissionsEnum::BRANDS_RESTORE);

        $this->brandService->restore($brand);

        return redirect()->route('brands');
    }
}
