<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

final class PermissionsController extends Controller
{
    public function index(Request $request): ApiCollection
    {
        $query = Permission::query();

        $filter = $request->string('filter', '')->value();

        if (! empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter): void {
                    $query->where('name', 'like', $filter);
                }
            );
        }

        if ($request->has('select')) {
            $query->select(explode(',', $request->string('select')->value()));
        }

        $response = $query->orderBy(
            $request->string('order_by', 'name')->value(),
            $request->string('order_direction', 'ASC')->value()
        )->paginate($request->integer('per_page', 10));

        return new ApiCollection($response);
    }
}
