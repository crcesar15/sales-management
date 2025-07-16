<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Product;

class ProductsMediaController extends Controller
{
    public function destroy($id, $media_id): JsonResponse
    {
        $media = Media::query()->find($media_id);

        if ($media && ($media->model_id === $id && $media->model_type === Product::class)) {
            $media->delete();
            return new JsonResponse(['message' => 'Media deleted'], 200);
        }

        return new JsonResponse(['message' => 'Media not found'], 404);
    }

    public function destroyDraft($id): JsonResponse
    {
        $media = Media::query()->find($id);

        if ($media) {
            $media->delete();

            return new JsonResponse(['message' => 'Media deleted'], 200);
        }

        return new JsonResponse(['message' => 'Media not found'], 404);
    }

    public function draft(): JsonResponse
    {
        $file = request()->file('file');

        if ($file) {
            $path = $file->store('products', 'public');
            $media = Media::query()->create([
                'model_id' => 0,
                'model_type' => Product::class,
                'filename' => explode('/', $path)[1],
            ]);

            return new JsonResponse(['data' => $media], 201);
        }

        return new JsonResponse(['message' => 'File not found'], 404);
    }

    public function store($id): JsonResponse
    {
        $product = Product::query()->find($id);

        if ($product) {
            $file = request()->file('file');

            if ($file) {
                $path = $file->store('products', 'public');
                $media = Media::query()->create([
                    'model_id' => $product->id,
                    'model_type' => Product::class,
                    'filename' => explode('/', $path)[1],
                ]);

                return new JsonResponse(['data' => $media], 201);
            }
        }

        return new JsonResponse(['message' => 'Product not found'], 404);
    }
}
