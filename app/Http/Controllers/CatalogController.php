<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
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

    public function edit($i)
    {
        $variant = ProductVariant::with([
            'product',
            'product.brand',
            'product.categories',
            'product.measureUnit',
        ])->where('id', $i)->first();

        return Inertia::render('catalog/edit/index', [
            'variant' => $variant,
        ]);
    }
}
