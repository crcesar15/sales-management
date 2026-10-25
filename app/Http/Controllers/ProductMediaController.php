<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Products\StoreProductMediaRequest;
use App\Models\PendingMediaUpload;
use App\Services\PendingMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class ProductMediaController extends Controller
{
    private readonly PendingMediaService $pendingMediaService;

    public function __construct(PendingMediaService $pendingMediaService)
    {
        $this->pendingMediaService = $pendingMediaService;
    }

    public function store(StoreProductMediaRequest $request): JsonResponse
    {
        $pending = $this->pendingMediaService->upload($request->file('file'));

        $media = $pending->getFirstMedia('temp');

        return response()->json([
            'id' => $pending->id,
            'thumb_url' => $media?->getUrl('thumb'),
            'full_url' => $media?->getUrl(),
        ], 201);
    }

    public function destroy(PendingMediaUpload $pendingMediaUpload): Response
    {
        $this->pendingMediaService->delete($pendingMediaUpload);

        return response()->noContent();
    }
}
