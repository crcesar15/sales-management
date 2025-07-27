<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use Inertia\Inertia;
use Inertia\Response;

final class CategoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize(PermissionsEnum::CATEGORY_VIEW->value, auth()->user());

        return Inertia::render('catalog/index');
    }
}
