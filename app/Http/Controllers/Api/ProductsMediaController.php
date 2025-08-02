<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class ProductsMediaController extends Controller
{
    public function destroy(Product $product, Media $media): JsonResponse|Response
    {
        if ($media->model_id === $product->id && $media->model_type === Product::class) {
            $media->delete();

            return response()->noContent();
        }

        return response()->json(['message' => 'Media not found'], 404);
    }

    public function store(Product $product): JsonResponse
    {
        $file = request()->file('file');

        if ($file) {
            // ! Review if $file->path works
            $file->store('products', 'public');
            $path = $file->path();

            $media = Media::query()->create([
                'model_id' => $product->id,
                'model_type' => Product::class,
                'filename' => explode('/', $path)[1],
            ]);

            return new JsonResponse($media, 201);
        }

        // TODO: validate with formRequest
        return response()->json(['message' => 'Something went wrong'], 422);
    }
}
