<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

final class ApiCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'filter' => htmlentities((string) $request->input('filter', '')),
                'sort_by' => $request->input('sort_by', ''),
                'sort_order' => $request->input('sort_order', ''),
                'count' => $this->resource->count(),
                'total_pages' => ceil($this->resource->total() / $this->resource->perPage()),
            ],
        ];
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function toResponse($request)
    {
        if ($this->resource instanceof Collection) {
            $this->resource = $this->collectionToPaginator($this->resource, $request);
        }

        return $this->resource instanceof AbstractPaginator
                    ? (new ApiPaginatedResourceResponse($this))->toResponse($request)
                    : parent::toResponse($request);
    }

    /**
     * Convert a Collection to a LengthAwarePaginator
     *
     * @param  \Illuminate\Support\Collection  $collection
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function collectionToPaginator(Collection $collection, Request $request)
    {
        $count = $this->totalCount ?? $collection->count();

        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 10);

        $startIndex = ($page - 1) * $perPage;
        $limit = $perPage;

        if ($this->collection->count() > $limit) {
            $this->collection = $collection->slice($startIndex, $limit)->values();
        }

        return new LengthAwarePaginator($this->collection, $count, $perPage);
    }
}
