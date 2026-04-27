<?php

declare(strict_types=1);

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MeasurementUnitController;
use App\Http\Controllers\OptionValueController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductMediaController;
use App\Http\Controllers\ProductOptionController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ProductVariantUnitController;
use App\Http\Controllers\PurchaseOrdersController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockOverviewController;
use App\Http\Controllers\StockTransferController;
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
    Route::post('/products/media/pending', [ProductMediaController::class, 'store'])->name('products.media.store');
    Route::delete('/products/media/pending/{pendingMediaUpload}', [ProductMediaController::class, 'destroy'])->name('products.media.destroy');
    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::put('/products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore')->withTrashed();

    // Product Options & Variants
    Route::post('/products/{product}/options', [ProductOptionController::class, 'store'])->name('option.store');
    Route::put('/products/{product}/options/{option}', [ProductOptionController::class, 'update'])->name('option.update');
    Route::delete('/products/{product}/options/{option}', [ProductOptionController::class, 'destroy'])->name('option.destroy');
    Route::post('/products/{product}/options/{option}/values', [OptionValueController::class, 'store'])->name('value.store');
    Route::delete('/products/{product}/options/{option}/values/{value}', [OptionValueController::class, 'destroy'])->name('value.destroy');
    Route::post('/products/{product}/variants/generate', [ProductVariantController::class, 'generate'])->name('variant.generate');
    Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])->name('variant.store');
    Route::put('/products/{product}/variants/{variant}', [ProductVariantController::class, 'update'])->name('variant.update');
    Route::delete('/products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy'])->name('variant.destroy');
    Route::put('/products/{product}/variants/{variant}/images', [ProductVariantController::class, 'syncImages'])->name('variant.images.sync');

    // Product Variant Units
    Route::scopeBindings()->group(function (): void {
        Route::post('/products/{product}/variants/{variant}/units', [ProductVariantUnitController::class, 'store'])->name('variant.units.store');
        Route::put('/products/{product}/variants/{variant}/units/{unit}', [ProductVariantUnitController::class, 'update'])->name('variant.units.update');
        Route::delete('/products/{product}/variants/{variant}/units/{unit}', [ProductVariantUnitController::class, 'destroy'])->name('variant.units.destroy');
    });

    Route::get('/gallery', fn () => Inertia::render('Gallery/Index'))->name('gallery');

    // Inventory Routes
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.variants');
    Route::get('/inventory/variants/{variant}', [InventoryController::class, 'show'])->name('inventory.variants.show');
    Route::get('/inventory/stock', fn () => redirect()->route('inventory.variants', request()->query()))->name('inventory.stock');
    Route::get('/inventory/stock/{variant}', [StockOverviewController::class, 'show'])->name('inventory.stock.show');

    // Batch Routes
    Route::get('/batches', [BatchController::class, 'index'])->name('batches');
    Route::get('/batches/{batch}', [BatchController::class, 'show'])->name('batches.show');
    Route::patch('/batches/{batch}/close', [BatchController::class, 'close'])->name('batches.close');

    // Stock Transfer Routes
    Route::get('/stock-transfers', [StockTransferController::class, 'index'])->name('stock-transfers');
    Route::get('/stock-transfers/create', [StockTransferController::class, 'create'])->name('stock-transfers.create');
    Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('stock-transfers.store');
    Route::get('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'show'])->name('stock-transfers.show');
    Route::patch('/stock-transfers/{stockTransfer}/status', [StockTransferController::class, 'updateStatus'])->name('stock-transfers.update-status');
    Route::patch('/stock-transfers/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');

    // Category Routes
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::put('/categories/{category}/restore', [CategoryController::class, 'restore'])->name('categories.restore')->withTrashed();

    // Brand Routes
    Route::get('/brands', [BrandController::class, 'index'])->name('brands');
    Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
    Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
    Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
    Route::put('/brands/{brand}/restore', [BrandController::class, 'restore'])->name('brands.restore')->withTrashed();

    // Measurement Unit Routes
    Route::get('/measurement-units', [MeasurementUnitController::class, 'index'])->name('measurement-units');
    Route::post('/measurement-units', [MeasurementUnitController::class, 'store'])->name('measurement-units.store');
    Route::put('/measurement-units/{measurementUnit}', [MeasurementUnitController::class, 'update'])->name('measurement-units.update');
    Route::delete('/measurement-units/{measurementUnit}', [MeasurementUnitController::class, 'destroy'])->name('measurement-units.destroy');
    Route::put('/measurement-units/{measurementUnit}/restore', [MeasurementUnitController::class, 'restore'])->name('measurement-units.restore')->withTrashed();

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
    Route::get('/settings', [SettingController::class, 'index'])->name('settings');
    Route::put('/settings/general', [SettingController::class, 'updateGeneral'])->name('settings.general.update');
    Route::put('/settings/tax', [SettingController::class, 'updateTax'])->name('settings.tax.update');
    Route::put('/settings/finance', [SettingController::class, 'updateFinance'])->name('settings.finance.update');

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs');
});
