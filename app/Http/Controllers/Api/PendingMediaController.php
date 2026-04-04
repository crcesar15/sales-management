<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PendingMediaUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class PendingMediaController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image'],
        ]);

        $pending = PendingMediaUpload::create([
            'user_id' => $request->user()?->id,
        ]);

        $media = $pending->addMediaFromRequest('file')->toMediaCollection('temp');

        return response()->json([
            'id' => $pending->id,
            'thumb_url' => $media->getUrl('thumb'),
            'full_url' => $media->getUrl(),
        ]);
    }

    public function destroy(int $id): Response
    {
        $pending = PendingMediaUpload::findOrFail($id);
        $pending->delete();

        return response()->noContent();
    }
}
