<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BatchesController extends Controller
{
    public function available(Request $request): JsonResponse
    {
        $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'store_id' => ['required', 'integer', 'exists:stores,id'],
        ]);

        $batches = Batch::query()
            ->available(
                $request->integer('product_variant_id'),
                $request->integer('store_id'),
            )
            ->get()
            ->map(fn (Batch $batch) => [
                'id' => $batch->id,
                'batch_identifier' => $batch->batch_identifier,
                'expiry_date' => $batch->getAttribute('expiry_date')?->toDateString(),
                'remaining_quantity' => $batch->remaining_quantity,
                'initial_quantity' => $batch->initial_quantity,
                'status' => $batch->status,
            ]);

        return response()->json(['data' => $batches]);
    }
}
