<?php
/**
 * Copyright (c) Since 2024 InnoShop - All Rights Reserved
 *
 * @link       https://www.innoshop.com
 * @author     InnoShop <team@innoshop.com>
 * @license    https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

use Illuminate\Support\Facades\Route;
use InnoShop\Front\ApiControllers;

Route::get('/home', [ApiControllers\HomeController::class, 'index'])->name('home.index');

Route::post('/login', [ApiControllers\AuthController::class, 'login'])->name('login.index');
Route::post('/register', [ApiControllers\AuthController::class, 'register'])->name('login.register');

Route::get('/categories', [ApiControllers\CategoryController::class, 'index'])->name('categories.index');
Route::get('/products', [ApiControllers\ProductController::class, 'index'])->name('products.index');

Route::get('/orders', [ApiControllers\OrderController::class, 'index'])->name('orders.index');

//Route::middleware(['auth:sanctum'])->group(function () {
//    Route::get('/orders', function () {
//        return [];
//    });
//});
