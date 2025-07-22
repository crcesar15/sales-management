<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class PurchaseOrdersController extends Controller
{
    //
    public function index(): InertiaResponse
    {
        return Inertia::render('purchase-orders/index');
    }

    public function create(): InertiaResponse
    {
        // current date
        $date = Carbon::now()->format('Y-m-d');

        // current time
        $time = Carbon::now()->format('H:i:s');

        return Inertia::render('purchase-orders/create/index', [
            'date' => $date,
            'time' => $time,
        ]);
    }
}
