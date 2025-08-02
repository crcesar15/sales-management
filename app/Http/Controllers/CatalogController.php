<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class CatalogController extends Controller
{
    /**
     * Display a listing of variants.
     */
    public function index(): InertiaResponse
    {
        return Inertia::render('Catalog/Index');
    }

    public function edit(ProductVariant $variant): InertiaResponse
    {
        $variant->load([
            'product',
            'product.brand',
            'product.categories',
            'product.measureUnit',
        ]);

        /**
         * @var Collection $catalog
         */
        $catalog = $variant->vendors()->get(); // @phpstan-ignore-line

        $vendors = Vendor::query()->limit(10)->get();

        return Inertia::render('Catalog/Edit/Index', [
            'variant' => $variant,
            'savedCatalog' => $catalog,
            'savedVendors' => $vendors,
        ]);
    }
}
