<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class PosController extends Controller
{
    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::POS_ACCESS);

        return Inertia::render('Pos/Index');
    }
}
