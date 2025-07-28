<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MeasurementUnitController;
use App\Http\Controllers\Api\PermissionsController;
use App\Http\Controllers\Api\ProductsController;
use App\Http\Controllers\Api\ProductsMediaController;
use App\Http\Controllers\Api\PurchaseOrdersController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VariantsController;
use App\Http\Controllers\Api\VendorsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', fn (Request $request) => $request->user());

// Routes for Products
Route::group(['middleware' => 'auth:sanctum', 'as' => 'api.'], function (): void {
    // Routes for Products
    Route::get('/products', [ProductsController::class, 'index'])->name('products');
    Route::get('/products/{product}', [ProductsController::class, 'show'])->name('products.show');
    Route::post('/products', [ProductsController::class, 'store'])->name('products.store');
    Route::put('/products/{product}', [ProductsController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductsController::class, 'destroy'])->name('products.destroy');

    // Routes for Categories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Routes for Brands
    Route::get('/brands', [BrandController::class, 'index'])->name('brands');
    Route::get('/brands/{brand}', [BrandController::class, 'show'])->name('brands.show');
    Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
    Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
    Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');

    // Routes for Measurement Units
    Route::get('/measurement-units', [MeasurementUnitController::class, 'index'])->name('measurement-units');
    Route::get('/measurement-units/{measurementUnit}', [MeasurementUnitController::class, 'show'])->name('measurement-units.show');
    Route::post('/measurement-units', [MeasurementUnitController::class, 'store'])->name('measurement-units.store');
    Route::put('/measurement-units/{measurementUnit}', [MeasurementUnitController::class, 'update'])
        ->name('measurement-units.update');
    Route::delete('/measurement-units/{measurementUnit}', [MeasurementUnitController::class, 'destroy'])
        ->name('measurement-units.destroy');

    // Products Media
    Route::post('/products/media', [ProductsMediaController::class, 'draft'])
        ->name('products.media.draft');
    Route::post('/products/{product}/media', [ProductsMediaController::class, 'store'])->name('products.media.store');
    Route::delete('/products/{product}/media/{media}', [ProductsMediaController::class, 'destroy'])
        ->name('products.media.destroy');
    Route::delete('products/media/draft/{media}', [ProductsMediaController::class, 'destroyDraft'])
        ->name('products.media.destroy-draft');

    // Routes for Roles
    Route::get('/roles', [RoleController::class, 'index'])->name('roles');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    // Routes for Users
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::put('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');

    // Routes for Vendors
    Route::get('/vendors', [VendorsController::class, 'index'])->name('vendors');
    Route::get('/vendors/{vendor}', [VendorsController::class, 'show'])->name('vendors.show');
    Route::post('/vendors', [VendorsController::class, 'store'])->name('vendors.store');
    Route::put('/vendors/{vendor}', [VendorsController::class, 'update'])->name('vendors.update');
    Route::delete('/vendors/{vendor}', [VendorsController::class, 'destroy'])->name('vendors.destroy');

    // Routes for Vendor Products
    Route::get('/vendors/{vendor}/variants', [VendorsController::class, 'getProductVariants'])
        ->name('vendors.variants');
    Route::post('/vendors/{vendor}/variants/{variant}', [VendorsController::class, 'storeProductVariant'])
        ->name('vendors.variants.store');
    Route::put('/vendors/{vendor}/variants/{variant}', [VendorsController::class, 'updateProductVariant'])
        ->name('vendors.variants.update');
    Route::delete('/vendors/{vendor}/variants/{variant}', [VendorsController::class, 'removeProductVariant'])
        ->name('vendors.variants.delete');

    // Routes for Product Variants
    Route::get('variants', [VariantsController::class, 'index'])->name('variants');

    // Routes for Variant Vendors
    Route::get('variants/{variant}/vendors', [VariantsController::class, 'getVendors'])->name('variants.vendors');
    Route::put('variants/{variant}/vendors', [VariantsController::class, 'updateVendors'])
        ->name('variants.vendors.update');

    // Routes for purchase orders
    Route::get('purchase-orders', [PurchaseOrdersController::class, 'index'])->name('purchase-orders');
    Route::get('purchase-orders/{order}', [PurchaseOrdersController::class, 'show'])->name('purchase-orders.show');
    Route::post('purchase-orders', [PurchaseOrdersController::class, 'store'])->name('purchase-orders.store');
    Route::put('purchase-orders/{order}', [PurchaseOrdersController::class, 'update'])->name('purchase-orders.update');
    Route::delete('purchase-orders/{order}', [PurchaseOrdersController::class, 'delete'])->name('purchase-orders.delete');

    // Routes for settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Routes for permissions
    Route::get('/permissions', [PermissionsController::class, 'index'])->name('permissions');
});
