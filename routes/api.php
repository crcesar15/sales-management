<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Routes for Products
Route::group(['middleware' => 'auth:sanctum', 'as' => 'api.'], function () {
    //Routes for Products
    Route::get('/products', 'App\Http\Controllers\Api\ProductsController@index');
    Route::get('/products/{id}', 'App\Http\Controllers\Api\ProductsController@show');
    Route::post('/products', 'App\Http\Controllers\Api\ProductsController@store');
    Route::put('/products/{id}', 'App\Http\Controllers\Api\ProductsController@update')->name('products.update');
    Route::delete('/products/{id}', 'App\Http\Controllers\Api\ProductsController@destroy')->name('products.destroy');

    //Routes for Categories
    Route::get('/categories', 'App\Http\Controllers\Api\CategoriesController@index');
    Route::get('/categories/{id}', 'App\Http\Controllers\Api\CategoriesController@show');
    Route::post('/categories', 'App\Http\Controllers\Api\CategoriesController@store');
    Route::put('/categories/{id}', 'App\Http\Controllers\Api\CategoriesController@update');
    Route::delete('/categories/{id}', 'App\Http\Controllers\Api\CategoriesController@destroy');

    //Products Media
    Route::post('/products/media', 'App\Http\Controllers\Api\ProductsMediaController@draft');
    Route::post('/products/{id}/media', 'App\Http\Controllers\Api\ProductsMediaController@store');
    Route::delete('/products/{id}/media/{media_id}', 'App\Http\Controllers\Api\ProductsMediaController@destroy');
    Route::delete('products/media/{media_id}', 'App\Http\Controllers\Api\ProductsMediaController@destroyDraft')->name('products.media.destroy-draft');
});
