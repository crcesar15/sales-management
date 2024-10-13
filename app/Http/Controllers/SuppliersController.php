<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SuppliersController extends Controller
{
    public function index()
    {
        return Inertia::render('suppliers/index');
    }

    public function create()
    {
        return Inertia::render('suppliers/create/index');
    }

    public function edit($id)
    {
        $supplier = Supplier::find($id);

        return Inertia::render('suppliers/edit/index', ['supplier' => $supplier]);
    }

    public function products($id)
    {
        $supplier = Supplier::find($id);

        return Inertia::render('suppliers/products/index', ['supplier' => $supplier]);
    }
}
