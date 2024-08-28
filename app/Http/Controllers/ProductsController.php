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
        // List of Categories
        $categories = Category::all();

        // List of Measure Units
        $measureUnits = MeasureUnit::all();

        // List of Brands
        $brands = Brand::all();

        return Inertia::render('products/create/index', [
            'categories' => $categories,
            'measureUnits' => $measureUnits,
            'brands' => $brands,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        // Include the product variants with media
        $product->load(['variants', 'categories', 'brand', 'measureUnit']);

        // List of Categories
        $categories = Category::all();

        // List of Measure Units
        $measureUnits = MeasureUnit::all();

        // List of Brands
        $brands = Brand::all();

        return Inertia::render('products/edit/index', [
            'product' => $product,
            'categories' => $categories,
            'measureUnits' => $measureUnits,
            'brands' => $brands,
        ]);
    }
}
