<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockMovementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Categories and Suppliers pages
    Route::get('/categories', function () {
        return Inertia::render('Categories');
    })->name('categories');

    Route::get('/suppliers', function () {
        return Inertia::render('Suppliers');
    })->name('suppliers');

    // Web-based API endpoints for frontend components (session authenticated)
    Route::prefix('api')->group(function () {
        // Core Resources
        Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);
        Route::apiResource('suppliers', \App\Http\Controllers\SupplierController::class);
        Route::apiResource('customers', \App\Http\Controllers\CustomerController::class);
        Route::apiResource('products', \App\Http\Controllers\ProductController::class);
        Route::apiResource('sales', \App\Http\Controllers\SaleController::class);
        Route::apiResource('employees', \App\Http\Controllers\EmployeeController::class);
        Route::apiResource('users', \App\Http\Controllers\UserController::class);
        Route::apiResource('stores', \App\Http\Controllers\StoreController::class);
        Route::apiResource('stock-movements', \App\Http\Controllers\StockMovementController::class);

        // Specialized endpoints
        Route::get('stock-movement-types', [\App\Http\Controllers\StockMovementController::class, 'getAdjustmentTypes']);
        Route::get('products/search', [\App\Http\Controllers\ProductController::class, 'search']);
        Route::get('products/low-stock', [\App\Http\Controllers\ProductController::class, 'lowStock']);
        Route::get('stores/analytics', [\App\Http\Controllers\StoreController::class, 'analytics']);
        
        // Manager Dashboard
        Route::prefix('manager-dashboard')->group(function () {
            Route::get('analytics', [\App\Http\Controllers\ManagerDashboardController::class, 'getAnalytics']);
            Route::get('realtime', [\App\Http\Controllers\ManagerDashboardController::class, 'getRealtimeStats']);
            Route::get('employee-metrics', [\App\Http\Controllers\ManagerDashboardController::class, 'getEmployeeMetrics']);
        });
        
        // Employee specific routes
        Route::prefix('employees')->group(function () {
            Route::get('analytics', [\App\Http\Controllers\EmployeeController::class, 'analytics']);
            Route::get('roles-permissions', [\App\Http\Controllers\EmployeeController::class, 'getRolesAndPermissions']);
            Route::post('{employee}/reset-password', [\App\Http\Controllers\EmployeeController::class, 'resetPassword']);
        });
    });
});

require __DIR__.'/auth.php';
