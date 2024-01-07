<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = [
            'data' => $this->collection,
            'meta' => [
                'filter' => htmlentities($request->input('filter', '')),
                'sort_by' => $request->input('sort_by', ''),
                'sort_order' => $request->input('sort_order', ''),
                'count' => $this->resource->count(),
                'total_pages' => ceil($this->resource->total() / $this->resource->perPage()),
            ],
        ];

        return $payload;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
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
     * @param  Request  $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function collectionToPaginator(Collection $collection, Request $request)
    {
        if ($this->totalCount === null) {
            $count = $collection->count();
        } else {
            $count = $this->totalCount;
        }

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
