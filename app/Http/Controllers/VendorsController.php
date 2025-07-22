<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Vendor;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class VendorsController extends Controller
{
    public function index(): InertiaResponse
    {
        return Inertia::render('vendors/index');
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('vendors/create/index');
    }

    public function edit(Vendor $vendor): InertiaResponse
    {
        return Inertia::render('vendors/edit/index', ['vendor' => $vendor]);
    }

    public function products(Vendor $vendor): InertiaResponse
    {
        return Inertia::render('vendors/products/index', ['vendor' => $vendor]);
    }
}
