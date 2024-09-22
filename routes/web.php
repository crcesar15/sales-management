<?php

use App\Http\Controllers\ProductsController;
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
    // Home Routes
    Route::get('/home', function () {
        return Inertia::render('dashboard/index');
    })->name('home');

    // Products Routes
    Route::get('/products', [ProductsController::class, 'index'])->name('products');
    Route::get('/products/{product}/edit', [ProductsController::class, 'edit'])->name('products.edit');
    Route::get('/products/create', [ProductsController::class, 'create'])->name('products.create');

    Route::get('/gallery', function () {
        return Inertia::render('gallery/index');
    })->name('gallery');

    Route::get('/categories', function () {
        return Inertia::render('category/index');
    })->name('categories');

    Route::get('/brands', function () {
        return Inertia::render('brands/index');
    })->name('brands');

    Route::get('/measure-units', function () {
        return Inertia::render('measure-units/index');
    })->name('measure-units');

    Route::get('/roles', function () {
        return Inertia::render('roles/index');
    })->name('roles');
});
