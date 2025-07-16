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

        $filter = $request->input('filter', '');

        if (! empty($filter)) {
            $filter = '%' . $filter . '%';
            $query->where(
                function ($query) use ($filter): void {
                    $query->where('name', 'like', $filter);
                }
            );
        }

        if ($request->has('select')) {
            $query->select(explode(',', (string) $request->input('select')));
        }

        $order_by = $request->has('order_by')
            ? $order_by = $request->get('order_by')
            : 'name';
        $order_direction = $request->has('order_direction')
            ? $request->get('order_direction')
            : 'ASC';

        $response = $query->orderBy(
            $request->input('order_by', $order_by),
            $request->input('order_direction', $order_direction)
        )->paginate($request->input('per_page', 10));

        return new ApiCollection($response);
    }
}
