<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\Webhook\PayMongoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'index'])->name('products.index');
Route::post('/products/{product}/sale', [ProductController::class, 'recordSale'])->name('products.sale');
Route::post('/products/{product}/restock', [ProductController::class, 'restock'])->name('products.restock');

Route::post('/webhooks/paymongo', [PayMongoController::class, 'handle'])
    ->name('webhooks.paymongo');
