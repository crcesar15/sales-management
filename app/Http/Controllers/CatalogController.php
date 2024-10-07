<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CatalogController extends Controller
{
    /**
     * Display a listing of variants.
     */
    public function index()
    {
        return Inertia::render('catalog/index');
    }
}
