<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;

class ProductsMediaController extends Controller
{
    public function destroy($id, $media_id)
    {
        $product = Product::find($id);
        $media = Media::find($media_id);

        if ($product && $media) {
            if ($product->id === $media->model_id && $media->model_type === Product::class) {
                $media->delete();

                return response()->json(['message' => 'Media deleted'], 200);
            }
        }

        return response()->json(['message' => 'Media not found'], 404);
    }

    public function destroyDraft($media_id)
    {
        $media = Media::find($media_id);

        if ($media) {
            if ($media->model_id === 0 && $media->model_type === ProductVariant::class) {
                $media->delete();

                return response()->json(['message' => 'Media deleted'], 200);
            }
        }

        return response()->json(['message' => 'Media not found'], 404);
    }

    public function draft()
    {
        $file = request()->file('file');

        if ($file) {
            $path = $file->store('products', 'public');
            $media = Media::create([
                'model_id' => 0,
                'model_type' => ProductVariant::class,
                'filename' => explode('/', $path)[1],
            ]);

            return response()->json(['data' => $media], 201);
        }

        return response()->json(['message' => 'File not found'], 404);
    }

    public function store($id)
    {
        $product = Product::find($id);

        if ($product) {
            $file = request()->file('file');

            if ($file) {
                $path = $file->store('products', 'public');
                $media = Media::create([
                    'model_id' => $product->id,
                    'model_type' => Product::class,
                    'filename' => explode('/', $path)[1],
                ]);

                return response()->json(['data' => $media], 201);
            }
        }

        return response()->json(['message' => 'Product not found'], 404);
    }
}
