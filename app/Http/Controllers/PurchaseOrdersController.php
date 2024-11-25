<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseOrdersController extends Controller
{
    //
    public function index()
    {
        return Inertia::render('purchase-orders/index');
    }

    public function create()
    {
        return Inertia::render('purchase-orders/create/index');
    }
}
