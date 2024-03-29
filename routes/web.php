<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    //check if user is logged in
    if (Auth::check()) {
        //user is logged in
        return redirect('/home');
    } else {
        //user is not logged in
        return redirect('/login');
    }
});

Auth::routes();

Route::group(['middleware' => ['auth']], function () {
    Route::get('/home', function () {
        return Inertia::render('dashboard/index');
    })->name('home');
    Route::get('/products', function () {
        return Inertia::render('products/index');
    })->name('products');

    Route::get('/products/{id}/edit', function () {
        return Inertia::render('products/ItemEditor');
    })->name('products.edit');

    Route::get('/gallery', function () {
        return Inertia::render('gallery/index');
    })->name('gallery');
    Route::get('/categories', function () {
        return Inertia::render('category/index');
    })->name('categories');
});
