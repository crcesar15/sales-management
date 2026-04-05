<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PendingMediaUpload;
use App\Services\Products\PendingMediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class ProductMediaController extends Controller
{
    private readonly PendingMediaService $pendingMediaService;

    public function __construct(PendingMediaService $pendingMediaService)
    {
        $this->pendingMediaService = $pendingMediaService;
    }

    public function store(): JsonResponse
    {
        request()->validate([
            'file' => ['required', 'image'],
        ]);

        $pending = $this->pendingMediaService->upload(request()->file('file'));

        $media = $pending->getFirstMedia('temp');

        return response()->json([
            'id' => $pending->id,
            'thumb_url' => $media?->getUrl('thumb'),
            'full_url' => $media?->getUrl(),
        ]);
    }

    public function destroy(PendingMediaUpload $pendingMediaUpload): Response
    {
        $this->pendingMediaService->delete($pendingMediaUpload);

        return response()->noContent();
    }
}
