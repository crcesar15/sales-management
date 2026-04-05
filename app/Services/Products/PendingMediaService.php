<?php

declare(strict_types=1);

namespace App\Services\Products;

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
     * @param  array<int>  $pendingMediaIds
     */
    public function commit(Product $product, array $pendingMediaIds): void
    {
        $userId = Auth::id();

        foreach ($pendingMediaIds as $pendingMediaId) {
            $pending = PendingMediaUpload::where('user_id', $userId)
                ->findOrFail($pendingMediaId);

            $media = $pending->getFirstMedia('temp');

            if ($media) {
                $media->move($product, 'images');
            }

            $pending->delete();
        }
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
