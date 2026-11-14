<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Inventory\LocationCodeSearchRequest;
use App\Models\Store;
use App\Services\VariantLocationService;
use Illuminate\Http\JsonResponse;

final class LocationCodeController extends Controller
{
    public function index(LocationCodeSearchRequest $request, Store $store, VariantLocationService $service): JsonResponse
    {
        return response()->json(['data' => $service->suggestions($store->id, $request->string('search')->toString())]);
    }
}
