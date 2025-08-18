<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
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

// Categories
Route::apiResource('categories', CategoryController::class);

// Suppliers
Route::apiResource('suppliers', SupplierController::class);

// Products
Route::get('products/search', [ProductController::class, 'search']);
Route::get('products/low-stock', [ProductController::class, 'lowStock']);
Route::apiResource('products', ProductController::class);

// Customers
Route::apiResource('customers', CustomerController::class);

// Sales
Route::apiResource('sales', SaleController::class);

// Stock Movements
Route::get('stock-movement-types', [StockMovementController::class, 'getAdjustmentTypes']);
Route::apiResource('stock-movements', StockMovementController::class);

// Stores
Route::apiResource('stores', StoreController::class);

// Settings
Route::get('settings/all', [SettingController::class, 'getAllSettings']);
Route::post('settings/store', [SettingController::class, 'saveStoreSettings']);
Route::post('settings/tax', [SettingController::class, 'saveTaxSettings']);
Route::post('settings/receipt', [SettingController::class, 'saveReceiptSettings']);
Route::apiResource('settings', SettingController::class);

// Users
Route::apiResource('users', UserController::class);

// Payments
Route::post('payments/process', [PaymentController::class, 'processPayment']);
Route::get('payment-methods', [PaymentController::class, 'getPaymentMethods']);
Route::get('payments/stats', [PaymentController::class, 'getStats']);
Route::get('sales/{sale}/payments', [PaymentController::class, 'getSalePayments']);
Route::get('payments/{payment}', [PaymentController::class, 'show']);
Route::post('payments/{payment}/refund', [PaymentController::class, 'refund']);
