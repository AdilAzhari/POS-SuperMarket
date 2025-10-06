<?php

declare(strict_types=1);

use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Sanctum CSRF cookie endpoint for API authentication
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
})->name('sanctum.csrf-cookie');

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Test route for development
Route::get('/test-dashboard', function () {
    return Inertia::render('Dashboard');
})->name('test-dashboard');

Route::middleware('auth')
    ->controller(ProfileController::class)
    ->as('profile')
    ->prefix('profile')
    ->group(function (): void {
        Route::get('/', 'edit')->name('.edit');
        Route::patch('/', 'update')->name('.update');
        Route::delete('/', 'destroy')->name('.destroy');

        // Categories and Suppliers pages
        Route::get('/categories', function () {
            return Inertia::render('Categories');
        })->name('categories');

        Route::get('/suppliers', function () {
            return Inertia::render('Suppliers');
        })->name('suppliers');

        // Web-based API endpoints for frontend components (session authenticated)
        Route::prefix('api')->group(function (): void {
            // Core Resources
            Route::apiResource('categories', App\Http\Controllers\CategoryController::class);
            Route::apiResource('suppliers', App\Http\Controllers\SupplierController::class);
            Route::apiResource('customers', App\Http\Controllers\CustomerController::class);
            Route::apiResource('products', App\Http\Controllers\ProductController::class);
            Route::apiResource('sales', App\Http\Controllers\SaleController::class);
            Route::apiResource('employees', EmployeeController::class);
            Route::apiResource('users', App\Http\Controllers\UserController::class);
            Route::apiResource('stores', App\Http\Controllers\StoreController::class);
            Route::apiResource('stock-movements', App\Http\Controllers\StockMovementController::class);

            // Specialized endpoints
            Route::get('stock-movement-types', [App\Http\Controllers\StockMovementController::class, 'getAdjustmentTypes']);
            Route::get('stock-movements/statistics', [App\Http\Controllers\StockMovementController::class, 'statistics']);
            Route::get('products/search', [App\Http\Controllers\ProductController::class, 'search']);
            Route::get('products/low-stock', [App\Http\Controllers\ProductController::class, 'lowStock']);
            Route::get('stores/analytics', [App\Http\Controllers\StoreController::class, 'analytics']);

            // Employee specific routes
            Route::controller(EmployeeController::class)
                ->prefix('employees')->group(function (): void {
                    Route::get('analytics', 'analytics');
                    Route::get('roles-permissions', 'getRolesAndPermissions');
                    Route::post('{employee}/reset-password', 'resetPassword');
                });
        });
    });

require __DIR__.'/auth.php';
