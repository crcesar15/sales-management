<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Supplier;
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

        $catalog = $variant->suppliers;

        $suppliers = Supplier::limit(10)->get();

        //merge catalog to suppliers
        $catalog->each(function ($item) use ($suppliers) {
            // check if item is already in suppliers
            $supplier = $suppliers->where('id', $item->id)->first();

            if (!$supplier) {
                $suppliers->push($item);
            }
        });

        return Inertia::render('catalog/edit/index', [
            'variant' => $variant,
            'savedCatalog' => $catalog,
            'savedSuppliers' => $suppliers,
        ]);
    }
}
