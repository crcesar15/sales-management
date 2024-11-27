<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
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
        //current date
        $date = Carbon::now()->format('Y-m-d');

        //current time
        $time = Carbon::now()->format('H:i:s');

        return Inertia::render('purchase-orders/create/index', [
            'date' => $date,
            'time' => $time,
        ]);
    }
}
