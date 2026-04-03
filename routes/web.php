<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MeasurementUnitController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\PurchaseOrdersController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    // check if user is logged in
    if (Auth::check()) {
        // user is logged in
        return redirect('/home');
    }

    // user is not logged in
    return Inertia::render('Auth/Login');
});

// Auth::routes();
// Auth routes
Route::group(['middleware' => ['guest']], function (): void {
    Route::get('/login', function () {
        // check if user is logged in
        if (Auth::check()) {
            // user is logged in
            return redirect('/home');
        }

        // user is not logged in
        return Inertia::render('Auth/Login');
    })->name('login');
    Route::post('login', [LoginController::class, 'login'])->name('login');
    Route::get('password/reset', fn () => Inertia::render('Auth/EmailRequest'))->name('password.reset.request');
    Route::get('password/reset/{token}', function (Request $request) {
        $token = $request->route()?->parameter('token') ?? '';
        $email = $request->query('email', '');

        return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => $email]);
    })->name('password.reset');
    Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.reset.update');
    Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
});

Route::group(['middleware' => ['auth']], function (): void {
    // Logout Route
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    // Home Routes
    Route::get('/home', fn () => Inertia::render('Dashboard/Index'))->name('home');

    // Products Routes
    Route::get('/products', [ProductsController::class, 'index'])->name('products');
    Route::get('/products/{product}/edit', [ProductsController::class, 'edit'])->name('products.edit');
    Route::get('/products/create', [ProductsController::class, 'create'])->name('products.create');

    Route::get('/gallery', fn () => Inertia::render('Gallery/Index'))->name('gallery');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');

    Route::get('/brands', [BrandController::class, 'index'])->name('brands');

    Route::get('/measurement-units', [MeasurementUnitController::class, 'index'])->name('measurement-units');

    // Role routes
    Route::get('/roles', [RoleController::class, 'index'])->name('roles');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    // User Routes
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::put('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore')->withTrashed();

    // Store Routes
    Route::get('/stores', [StoreController::class, 'index'])->name('stores');
    Route::get('/stores/create', [StoreController::class, 'create'])->name('stores.create');
    Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
    Route::get('/stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
    Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');
    Route::delete('/stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');
    Route::put('/stores/{store}/restore', [StoreController::class, 'restore'])->name('stores.restore')->withTrashed();
    Route::patch('/stores/{store}/status', [StoreController::class, 'updateStatus'])->name('stores.status');

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

    // Settings
    Route::get('/settings', fn () => Inertia::render('Settings/Index'))->name('settings');

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs');
});
