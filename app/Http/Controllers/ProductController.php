<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Resources\Product\ProductCollection;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Services\Products\ProductService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class ProductController extends Controller
{
    private readonly ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::PRODUCTS_VIEW);

        $status = request()->string('status', 'active')->value();

        $products = $this->productService->list(
            status: $status,
            filter: request()->string('filter')->value() ?: null,
            brandId: request()->integer('brand_id') ?: null,
            categoryId: request()->integer('category_id') ?: null,
            orderBy: request()->string('order_by', 'name')->value(),
            orderDirection: request()->string('order_direction', 'asc')->value(),
            perPage: request()->integer('per_page', 10),
        );

        return Inertia::render('Products/Index', [
            'products' => new ProductCollection($products),
            'filters' => [
                'filter' => request()->string('filter')->value() ?: null,
                'status' => $status,
                'brand_id' => request()->integer('brand_id') ?: null,
                'category_id' => request()->integer('category_id') ?: null,
                'order_by' => request()->string('order_by', 'name')->value(),
                'order_direction' => request()->string('order_direction', 'asc')->value(),
                'per_page' => request()->integer('per_page', 20),
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::PRODUCTS_CREATE);

        return Inertia::render('Products/Create/Index', [
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'measurementUnits' => MeasurementUnit::orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->productService->create($request->validated());

        return redirect()->route('products');
    }

    public function edit(Product $product): InertiaResponse
    {
        $this->authorize(PermissionsEnum::PRODUCTS_EDIT);

        $product->load([
            'brand',
            'categories',
            'measurementUnit',
            'media',
            'variants.values.option',
            'variants.images',
            'options.values',
        ]);

        return Inertia::render('Products/Edit/Index', [
            'product' => [
                ...$product->toArray(),
                'brand_id' => $product->brand_id,
                'measurement_unit_id' => $product->measurement_unit_id,
                'categories' => $product->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]),
                'media' => $product->getMedia('images')->map(fn ($m) => [
                    'id' => $m->id,
                    'thumb_url' => $m->getUrl('thumb'),
                    'full_url' => $m->getUrl(),
                ]),
                'variants' => $product->variants->map(fn ($v) => [
                    'id' => $v->id,
                    'name' => $v->name,
                    'price' => (float) $v->price,
                    'stock' => $v->stock,
                    'barcode' => $v->barcode,
                    'identifier' => $v->identifier,
                    'status' => $v->status,
                    'values' => $v->values->map(fn ($val) => [
                        'id' => $val->id,
                        'value' => $val->value,
                        'option_name' => $val->option?->name,
                    ]),
                    'images' => $v->images->map(fn ($img) => [
                        'id' => $img->id,
                        'thumb_url' => $img->getUrl('thumb'),
                        'full_url' => $img->getUrl(),
                    ]),
                ]),
                'options' => $product->options->map(fn ($o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                    'values' => $o->values->map(fn ($v) => [
                        'id' => $v->id,
                        'value' => $v->value,
                    ]),
                ]),
                'has_variants' => $product->options()->exists(),
            ],
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'measurementUnits' => MeasurementUnit::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->update($product, $request->validated());

        return redirect()->route('products');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize(PermissionsEnum::PRODUCTS_DELETE);

        try {
            $this->productService->delete($product);
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return redirect()->route('products');
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize(PermissionsEnum::PRODUCTS_RESTORE);

        $this->productService->restore($id);

        return redirect()->route('products');
    }
}
