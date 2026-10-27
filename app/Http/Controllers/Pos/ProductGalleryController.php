<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pos;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\ProductGallery\IndexProductGalleryRequest;
use App\Http\Resources\ProductGallery\ProductGalleryVariantCollection;
use App\Services\ProductGalleryService;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class ProductGalleryController extends Controller
{
    public function __construct(private readonly ProductGalleryService $productGalleryService) {}

    public function index(IndexProductGalleryRequest $request): InertiaResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS);

        $filter = $request->string('filter')->value();
        $brandId = $request->integer('brand_id') ?: null;
        $categoryId = $request->integer('category_id') ?: null;

        return Inertia::render('Pos/ProductGallery/Index', [
            'variants' => new ProductGalleryVariantCollection($this->productGalleryService->list(
                filter: $filter,
                brandId: $brandId,
                categoryId: $categoryId,
                available: $request->boolean('available'),
            )),
            'filters' => [
                'filter' => $filter,
                'brand_id' => $brandId,
                'category_id' => $categoryId,
                'available' => $request->boolean('available'),
            ],
            'filterOptions' => $this->productGalleryService->filterOptions(),
        ]);
    }
}
