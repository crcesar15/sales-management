<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductGallery\ProductGalleryVariantDetailResource;
use App\Models\ProductVariant;
use App\Services\ProductGalleryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductGalleryController extends Controller
{
    public function __construct(private readonly ProductGalleryService $productGalleryService) {}

    public function show(Request $request, ProductVariant $variant): JsonResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS->value, $request->user());

        return (new ProductGalleryVariantDetailResource($this->productGalleryService->find($variant->id)))
            ->response()
            ->setStatusCode(200);
    }
}
