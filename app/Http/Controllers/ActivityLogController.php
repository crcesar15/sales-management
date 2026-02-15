<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use Inertia\Inertia;
use Inertia\Response;

final class ActivityLogController extends Controller
{
    public function index(): Response
    {
        $this->authorize(PermissionsEnum::ACTIVITY_LOGS_VIEW, auth()->user());

        return Inertia::render('ActivityLogs/Index');
    }
}
