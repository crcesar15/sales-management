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
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/products', function () {
        return Inertia::render('products/index');
    })->name('products');

    Route::get('/products/{any?}', function () {
        return view('products.index');
    })->name('products')->where('any', '.*');

    Route::get('/gallery', function () {
        return Inertia::render('gallery/index');
    })->name('gallery');
    Route::get('/dashboard', function () {
        return Inertia::render('dashboard/index');
    })->name('dashboard');
    Route::get('/categories', function () {
        return Inertia::render('category/index');
    })->name('categories');
});
