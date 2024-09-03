<?php

use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\ProductsController;
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

    //Products Media
    Route::post('/products/media', 'App\Http\Controllers\Api\ProductsMediaController@draft');
    Route::post('/products/{id}/media', 'App\Http\Controllers\Api\ProductsMediaController@store');
    Route::delete('/products/{id}/media/{media_id}', 'App\Http\Controllers\Api\ProductsMediaController@destroy');
    Route::delete('products/media/{media_id}', 'App\Http\Controllers\Api\ProductsMediaController@destroyDraft')->name('products.media.destroy-draft');
});
