<?php

use App\Http\Controllers\Api\BrandsController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\MeasureUnitsController;
use App\Http\Controllers\Api\ProductsController;
use App\Http\Controllers\Api\ProductsMediaController;
use App\Http\Controllers\Api\RolesController;
use App\Http\Controllers\Api\SuppliersController;
use App\Http\Controllers\Api\UsersController;
use App\Http\Controllers\Api\VariantsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Routes for Products
Route::group(['middleware' => 'auth:sanctum', 'as' => 'api.'], function () {
    //Routes for Products
    Route::get('/products', [ProductsController::class, 'index'])->name('products');
    Route::get('/products/{id}', [ProductsController::class, 'show'])->name('products.show');
    Route::post('/products', [ProductsController::class, 'store'])->name('products.store');
    Route::put('/products/{id}', [ProductsController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [ProductsController::class, 'destroy'])->name('products.destroy');

    //Routes for Categories
    Route::get('/categories', [CategoriesController::class, 'index'])->name('categories');
    Route::get('/categories/{id}', [CategoriesController::class, 'show'])->name('categories.show');
    Route::post('/categories', [CategoriesController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}', [CategoriesController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [CategoriesController::class, 'destroy'])->name('categories.destroy');

    //Routes for Brands
    Route::get('/brands', [BrandsController::class, 'index'])->name('brands');
    Route::get('/brands/{id}', [BrandsController::class, 'show'])->name('brands.show');
    Route::post('/brands', [BrandsController::class, 'store'])->name('brands.store');
    Route::put('/brands/{id}', [BrandsController::class, 'update'])->name('brands.update');
    Route::delete('/brands/{id}', [BrandsController::class, 'destroy'])->name('brands.destroy');

    //Routes for Measure Units
    Route::get('/measure-units', [MeasureUnitsController::class, 'index'])->name('measure-units');
    Route::get('/measure-units/{id}', [MeasureUnitsController::class, 'show'])->name('measure-units.show');
    Route::post('/measure-units', [MeasureUnitsController::class, 'store'])->name('measure-units.store');
    Route::put('/measure-units/{id}', [MeasureUnitsController::class, 'update'])->name('measure-units.update');
    Route::delete('/measure-units/{id}', [MeasureUnitsController::class, 'destroy'])->name('measure-units.destroy');

    //Products Media
    Route::post('/products/media', [ProductsMediaController::class, 'draft'])
        ->name('products.media.draft');
    Route::post('/products/{id}/media', [ProductsMediaController::class, 'store'])->name('products.media.store');
    Route::delete('/products/{id}/media/{media_id}', [ProductsMediaController::class, 'destroy'])
        ->name('products.media.destroy');
    Route::delete('products/media/{media_id}', [ProductsMediaController::class, 'destroyDraft'])
        ->name('products.media.destroy-draft');

    // Routes for Roles
    Route::get('/roles', [RolesController::class, 'index'])->name('roles');
    Route::get('/roles/{id}', [RolesController::class, 'show'])->name('roles.show');
    Route::post('/roles', [RolesController::class, 'store'])->name('roles.store');
    Route::put('/roles/{id}', [RolesController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{id}', [RolesController::class, 'destroy'])->name('roles.destroy');

    // Routes for Users
    Route::get('/users', [UsersController::class, 'index'])->name('users');
    Route::get('/users/{id}', [UsersController::class, 'show'])->name('users.show');
    Route::post('/users', [UsersController::class, 'store'])->name('users.store');
    Route::put('/users/{id}', [UsersController::class, 'update'])->name('users.update');
    Route::delete('/users/{id}', [UsersController::class, 'destroy'])->name('users.destroy');
    Route::put('/users/{id}/restore', [UsersController::class, 'restore'])->name('users.restore');

    // Routes for Suppliers
    Route::get('/suppliers', [SuppliersController::class, 'index'])->name('suppliers');
    Route::get('/suppliers/{id}', [SuppliersController::class, 'show'])->name('suppliers.show');
    Route::post('/suppliers', [SuppliersController::class, 'store'])->name('suppliers.store');
    Route::put('/suppliers/{id}', [SuppliersController::class, 'update'])->name('suppliers.update');
    Route::delete('/suppliers/{id}', [SuppliersController::class, 'destroy'])->name('suppliers.destroy');

    // Routes for Product Variants
    Route::get('variants', [VariantsController::class, 'index'])->name('variants');
});
