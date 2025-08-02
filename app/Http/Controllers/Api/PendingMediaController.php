<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PendingMedia;
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

        $uuid = uuid_create();

        $pending = PendingMedia::firstOrCreate([
            'upload_token' => $uuid,
        ]);

        $pending->addMediaFromRequest('file')->toMediaCollection('temp');

        return response()->json([
            'id' => $uuid,
            'url' => $pending->getMedia('temp')[0]->getUrl(),
        ]);
    }

    public function destroy(string $uuid): Response
    {
        PendingMedia::where('upload_token', $uuid)->firstOrFail()->deleteOrFail();

        return response()->noContent();
    }
}
