<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Product;

class ProductsMediaController extends Controller
{
    public function destroy($id, $media_id)
    {
        $media = Media::find($media_id);

        if ($media) {
            if ($media->model_id === $id && $media->model_type === Product::class) {
                $media->delete();

                return response()->json(['message' => 'Media deleted'], 200);
            }
        }

        return response()->json(['message' => 'Media not found'], 404);
    }

    public function destroyDraft($id)
    {
        $media = Media::find($id);

        if ($media) {
            $media->delete();

            return response()->json(['message' => 'Media deleted'], 200);
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
                'model_type' => Product::class,
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
