<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PermissionsEnum;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MeasurementUnit;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::PRODUCTS_VIEW->value, auth()->user());

        return Inertia::render('Products/Index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): InertiaResponse
    {
        $this->authorize(PermissionsEnum::PRODUCTS_CREATE->value, auth()->user());

        // List of Categories
        $categories = Category::all();

        // List of Measurement Units
        $measureUnits = MeasurementUnit::all();

        // List of Brands
        $brands = Brand::all();

        return Inertia::render('Products/Create/Index', [
            'categories' => $categories,
            'measureUnits' => $measureUnits,
            'brands' => $brands,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): InertiaResponse
    {
        $this->authorize(PermissionsEnum::PRODUCTS_EDIT->value, auth()->user());

        // Include the product variants with media
        $product->load(['media', 'variants', 'categories', 'brand', 'measureUnit']);

        // List of Categories
        $categories = Category::all();

        // List of Measurement Units
        $measureUnits = MeasurementUnit::all();

        // List of Brands
        $brands = Brand::all();

        return Inertia::render('Products/Edit/Index', [
            'product' => $product,
            'categories' => $categories,
            'measureUnits' => $measureUnits,
            'brands' => $brands,
        ]);
    }
}
