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
        return Inertia::render('Vendors/Index');
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('Vendors/Create/Index');
    }

    public function edit(Vendor $vendor): InertiaResponse
    {
        return Inertia::render('Vendors/Edit/Index', ['vendor' => $vendor]);
    }

    public function products(Vendor $vendor): InertiaResponse
    {
        return Inertia::render('Vendors/Products/Index', ['vendor' => $vendor]);
    }
}
