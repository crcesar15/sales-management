<?php

use App\Http\Controllers\Api\BrandsController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\MeasureUnitsController;
use App\Http\Controllers\Api\ProductsController;
use App\Http\Controllers\Api\ProductsMediaController;
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
});
