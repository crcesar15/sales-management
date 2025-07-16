<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Inertia\Inertia;

class VendorsController extends Controller
{
    public function index()
    {
        return Inertia::render('vendors/index');
    }

    public function create()
    {
        return Inertia::render('vendors/create/index');
    }

    public function edit($id)
    {
        $vendor = Vendor::query()->find($id);

        return Inertia::render('vendors/edit/index', ['vendor' => $vendor]);
    }

    public function products($id)
    {
        $vendor = Vendor::query()->find($id);

        return Inertia::render('vendors/products/index', ['vendor' => $vendor]);
    }
}
