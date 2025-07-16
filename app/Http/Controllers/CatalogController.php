<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Vendor;
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

        $catalog = $variant->vendors;

        $vendors = Vendor::query()->limit(10)->get();

        //merge catalog to vendors
        $catalog->each(function ($item) use ($vendors): void {
            // check if item is already in vendors
            $vendor = $vendors->where('id', $item->id)->first();

            if (!$vendor) {
                $vendors->push($item);
            }
        });

        return Inertia::render('catalog/edit/index', [
            'variant' => $variant,
            'savedCatalog' => $catalog,
            'savedVendors' => $vendors,
        ]);
    }
}
