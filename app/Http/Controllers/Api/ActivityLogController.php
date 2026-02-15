<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ActivityLogs\ListActivityLogRequest;
use App\Http\Resources\ActivityLogCollection;
use Spatie\Activitylog\Models\Activity;

final class ActivityLogController extends Controller
{
    public function index(ListActivityLogRequest $request): ActivityLogCollection
    {
        $request->validated();

        $query = Activity::query()->with('causer');

        if ($request->filled('filter')) {
            $filterValue = '%' . $request->string('filter')->value() . '%';
            $query->where(function ($q) use ($filterValue): void {
                $q->where('description', 'like', $filterValue)
                    ->orWhere('subject_type', 'like', $filterValue)
                    ->orWhere('log_name', 'like', $filterValue);
            });
        }

        if ($request->filled('event')) {
            $query->where('event', $request->string('event')->value());
        }

        $query->orderBy(
            $request->string('order_by')->value(),
            $request->string('order_direction')->value()
        );

        $response = $query->paginate($request->integer('per_page'));

        return new ActivityLogCollection($response);
    }
}
