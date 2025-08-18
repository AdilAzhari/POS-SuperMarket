<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StockMovementController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
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

    // API routes for adjustment types
    Route::get('/stock-movement-types', [StockMovementController::class, 'getAdjustmentTypes']);
});

require __DIR__.'/auth.php';
