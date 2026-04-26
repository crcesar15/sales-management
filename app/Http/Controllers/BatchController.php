<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Http\Requests\Batches\CloseBatchRequest;
use App\Http\Resources\Batches\BatchCollection;
use App\Http\Resources\Batches\BatchResource;
use App\Models\Batch;
use App\Models\Store;
use App\Services\BatchService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use InvalidArgumentException;
use RuntimeException;

final class BatchController extends Controller
{
    public function __construct(
        private readonly BatchService $batchService,
    ) {}

    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::BATCHES_VIEW);

        $batches = $this->batchService->list(
            filters: [
                'status' => request()->string('status', '')->toString() ?: null,
                'store_id' => request()->integer('store_id') ?: null,
                'product_variant_id' => request()->integer('product_variant_id') ?: null,
                'expiry_from' => request()->string('expiry_from', '')->toString() ?: null,
                'expiry_to' => request()->string('expiry_to', '')->toString() ?: null,
                'expiring_soon' => request()->boolean('expiring_soon'),
            ],
            perPage: request()->integer('per_page', 25),
        );

        return Inertia::render('Batches/Index', [
            'batches' => new BatchCollection($batches),
            'filters' => [
                'status' => request()->string('status', '')->toString(),
                'store_id' => request()->integer('store_id') ?: null,
                'product_variant_id' => request()->integer('product_variant_id') ?: null,
                'expiry_from' => request()->string('expiry_from', '')->toString() ?: null,
                'expiry_to' => request()->string('expiry_to', '')->toString() ?: null,
                'expiring_soon' => request()->boolean('expiring_soon'),
            ],
            'stores' => Store::query()->where('status', 'active')->get(['id', 'name', 'code']),
        ]);
    }

    public function show(Batch $batch): InertiaResponse
    {
        $this->authorize(PermissionsEnum::BATCHES_VIEW);

        $batch = $this->batchService->getDetail($batch);

        return Inertia::render('Batches/Show/Index', [
            'batch' => (new BatchResource($batch))->resolve(),
        ]);
    }

    public function close(CloseBatchRequest $request, Batch $batch): RedirectResponse
    {
        try {
            $this->batchService->closeBatch(
                batch: $batch,
                notes: $request->string('notes', '')->toString() ?: null,
                actor: $request->user() ?? throw new RuntimeException('Unauthenticated.'),
            );
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->withErrors(['batch' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Batch closed successfully.');
    }
}
