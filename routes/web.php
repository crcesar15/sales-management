<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\SuppliersController;
use App\Http\Controllers\UsersController;
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

    // User Routes
    Route::get('/users', [UsersController::class, 'index'])->name('users');
    Route::get('/users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
    Route::get('/users/create', [UsersController::class, 'create'])->name('users.create');

    // Suppliers Routes
    Route::get('/suppliers', [SuppliersController::class, 'index'])->name('suppliers');
    Route::get('/suppliers/create', [SuppliersController::class, 'create'])->name('suppliers.create');
    Route::get('/suppliers/{supplier}/edit', [SuppliersController::class, 'edit'])->name('suppliers.edit');

    // Catalog Routes
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
    Route::get('/catalog/{variant}/edit', [CatalogController::class, 'edit'])->name('catalog.edit');
});
