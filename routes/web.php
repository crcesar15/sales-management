<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\PurchaseOrdersController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\VendorsController;
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

    // Role routes
    Route::get('/roles', [RolesController::class, 'index'])->name('roles');
    Route::get('/roles/create', [RolesController::class, 'create'])->name('roles.create');
    Route::get('/roles/{role}/edit', [RolesController::class, 'edit'])->name('roles.edit');

    // User Routes
    Route::get('/users', [UsersController::class, 'index'])->name('users');
    Route::get('/users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
    Route::get('/users/create', [UsersController::class, 'create'])->name('users.create');

    // Vendors Routes
    Route::get('/vendors', [VendorsController::class, 'index'])->name('vendors');
    Route::get('/vendors/create', [VendorsController::class, 'create'])->name('vendors.create');
    Route::get('/vendors/{vendor}/edit', [VendorsController::class, 'edit'])->name('vendors.edit');
    Route::get('/vendors/{vendor}/products', [VendorsController::class, 'products'])->name('vendors.products');

    // Catalog Routes
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
    Route::get('/catalog/{variant}/edit', [CatalogController::class, 'edit'])->name('catalog.edit');

    // Purchase Orders Routes
    Route::get('/purchase-orders', [PurchaseOrdersController::class, 'index'])->name('purchase-orders');
    Route::get('/purchase-orders/create', [PurchaseOrdersController::class, 'create'])->name('purchase-orders.create');

    //Settings
    Route::get('/settings', function () {
        return Inertia::render('settings/index');
    })->name('settings');
});
