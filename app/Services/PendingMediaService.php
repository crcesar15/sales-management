<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PendingMediaUpload;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

final class PendingMediaService
{
    public function upload(UploadedFile $file): PendingMediaUpload
    {
        $pending = PendingMediaUpload::create([
            'user_id' => Auth::id(),
        ]);

        $pending->addMedia($file)->toMediaCollection('temp');

        return $pending->refresh();
    }

    /**
     * Commit pending media to a product and return a mapping of pending IDs to media IDs.
     *
     * @param  array<int>  $pendingMediaIds
     * @return array<int, int> Mapping of pending_media_uploads.id → media.id
     */
    public function commit(Product $product, array $pendingMediaIds): array
    {
        $userId = Auth::id();
        $pendingMediaMap = [];

        foreach ($pendingMediaIds as $pendingMediaId) {
            $pending = PendingMediaUpload::where('user_id', $userId)
                ->findOrFail($pendingMediaId);

            $media = $pending->getFirstMedia('temp');

            if ($media) {
                $movedMedia = $media->move($product, 'images');
                $pendingMediaMap[$pendingMediaId] = $movedMedia->id;
            }

            $pending->delete();
        }

        return $pendingMediaMap;
    }

    public function delete(PendingMediaUpload $pendingMedia): void
    {
        $pendingMedia->delete();
    }

    public function purge(): void
    {
        PendingMediaUpload::where('created_at', '<', now()->subDay())->get()->each->delete();
    }
}
