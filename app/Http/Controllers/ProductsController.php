<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\MeasureUnit;
use App\Models\Product;
use Inertia\Inertia;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('products/index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('products/ItemCreator');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        // Include the product category
        $product->load(['media']);

        // List of Categories
        $categories = Category::all();

        // List of Measure Units
        $measureUnits = MeasureUnit::all();

        // List of Brands
        $brands = Brand::all();

        return Inertia::render('products/ItemEditor', [
            'product' => $product,
            'categories' => $categories,
            'measureUnits' => $measureUnits,
            'brands' => $brands,
        ]);
    }
}
