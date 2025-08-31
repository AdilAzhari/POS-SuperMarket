<?php

declare(strict_types=1);

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\ManagerDashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReorderController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
Route::apiResource('categories', CategoryController::class)
    ->middleware('auth');

// Suppliers
Route::apiResource('suppliers', SupplierController::class)
    ->middleware('auth');

// Products
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('products/search', [ProductController::class, 'search']);
    Route::get('products/low-stock', [ProductController::class, 'lowStock']);
    Route::apiResource('products', ProductController::class);
});

// Inventory Alerts
Route::controller(App\Http\Controllers\InventoryAlertController::class)
    ->prefix('inventory-alerts')
    ->group(function (): void {
        Route::get('/', 'index');
        Route::get('/dashboard', 'dashboard');
        Route::get('/critical', 'critical');
        Route::get('/store/{storeId}', 'store');
        Route::get('/store/{storeId}/reorder-suggestions', 'reorderSuggestions');
        Route::get('/store/{storeId}/pos-alerts', 'posAlerts');
        Route::get('/store/{storeId}/automatic-reorder-recommendations', 'automaticReorderRecommendations');
        Route::get('/store/{storeId}/reorder-integration', 'reorderIntegration');
        Route::post('/send-alerts', 'sendAlerts');
        Route::post('/store/{storeId}/update-thresholds', 'updateThresholds');
        Route::delete('/cache', 'clearCache');
    });

// Customers
Route::apiResource('customers', CustomerController::class)
    ->middleware('auth:sanctum');

// Sales
Route::apiResource('sales', SaleController::class)
    ->middleware('auth');

// Receipts
Route::controller(App\Http\Controllers\ReceiptController::class)
    ->prefix('receipts')
    ->group(function (): void {
        Route::get('/settings', 'settings');
        Route::put('/settings', 'updateSettings');
        Route::post('/reprint', 'reprint');
        Route::get('/{sale}', 'show')->name('receipts.show');
        Route::get('/{sale}/generate', 'generate');
        Route::post('/{sale}/print', 'print');
        Route::get('/{sale}/thermal', 'thermal');
        Route::get('/{sale}/pdf', 'pdf');
    });

// Stock Movements
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('stock-movement-types', [StockMovementController::class, 'getAdjustmentTypes']);
    Route::post('stock-movements/bulk', [StockMovementController::class, 'bulkStore']);
    Route::post('stock-movements/transfer', [StockMovementController::class, 'transfer']);
    Route::get('stock-movements/statistics', [StockMovementController::class, 'statistics']);
    Route::post('stock-movements/validate', [StockMovementController::class, 'validateStock']);
    Route::post('stock-movements/validate-multiple', [StockMovementController::class, 'validateMultipleStock']);
    Route::post('stock-movements/check-low-stock', [StockMovementController::class, 'checkLowStock']);
    Route::get('products/{product}/stock-summary', [StockMovementController::class, 'productStockSummary']);
    Route::apiResource('stock-movements', StockMovementController::class);
});

// Stores
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('stores/analytics', [StoreController::class, 'analytics']);
    Route::get('stores/{store}/validate', [StoreController::class, 'validate']);
    Route::apiResource('stores', StoreController::class);
});

// Settings
Route::controller(SettingController::class)
    ->prefix('settings')
    ->group(function (): void {
        Route::get('/all', 'getAllSettings');
        Route::get('/receipt', 'getReceiptSettings');
        Route::post('/store', 'saveStoreSettings');
        Route::post('/tax', 'saveTaxSettings');
        Route::post('/receipt', 'saveReceiptSettings');
    });

Route::apiResource('settings', SettingController::class)
    ->middleware('auth');

// Users
Route::apiResource('users', UserController::class)
    ->middleware('auth');

// Payments
Route::controller(PaymentController::class)
    ->group(function (): void {
        Route::post('payments/process', 'processPayment');
        Route::get('payment-methods', 'getPaymentMethods');
        Route::get('payments/stats', 'getStats');
        Route::get('payments/list', 'index'); // Changed to avoid conflict
        Route::get('sales/{sale}/payments', 'getSalePayments');
        Route::get('payments/{payment}', 'show');
        Route::post('payments/{payment}/refund', 'refund');
    });

// Employees
Route::controller(EmployeeController::class)
    ->prefix('employees')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/analytics', 'analytics');
        Route::get('/roles-permissions', 'getRolesAndPermissions');
        Route::post('/{employee}/reset-password', 'resetPassword');
    });
Route::apiResource('employees', EmployeeController::class)
    ->middleware('auth:sanctum');

// Loyalty Program
Route::controller(LoyaltyController::class)
    ->prefix('loyalty')
    ->group(function (): void {
        Route::get('/analytics', 'analytics');
        Route::get('/config', 'getConfig');
        Route::get('/rewards', 'getRewards');
        Route::get('/rewards/available/{customer}', 'getAvailableRewards');
        Route::post('/rewards', 'createReward');
        Route::put('/rewards/{reward}', 'updateReward');
        Route::delete('/rewards/{reward}', 'deleteReward');
        Route::post('/redeem', 'redeemReward');
        Route::get('/redemptions', 'getRedemptions');
        Route::get('/transactions/{customer}', 'getTransactions');
        Route::post('/apply-discount', 'applyDiscount');
        Route::post('/calculate-points', 'calculatePoints');
        Route::post('/birthday-rewards', 'sendBirthdayRewards');
        Route::post('/expire-points', 'expireOldPoints');
        Route::get('/customers/{customer}/summary', 'getCustomerSummary');
        Route::post('/customers/{customer}/adjust-points', 'adjustPoints');
    });

// Manager Dashboard - Real-time Operations
Route::controller(ManagerDashboardController::class)
    ->prefix('manager-dashboard')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/overview', 'getDashboardOverview');
        Route::get('/realtime-stats', 'getRealtimeStats');
        Route::get('/active-staff', 'getActiveStaffMetrics');
        Route::post('/quick-action', 'executeQuickAction');
    });

// Reports & Analytics - Historical Analysis
Route::controller(App\Http\Controllers\ReportsAnalyticsController::class)
    ->prefix('reports')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/analytics', 'getAnalytics');
        Route::get('/sales', 'getSalesReports');
        Route::get('/inventory', 'getInventoryReports');
        Route::get('/employees', 'getEmployeeReports');
        Route::get('/customers', 'getCustomerReports');
        Route::post('/export', 'exportReport');
    });

// Purchase Orders
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('purchase-orders/{purchaseOrder}/mark-ordered', [PurchaseOrderController::class, 'markOrdered']);
    Route::post('purchase-orders/{purchaseOrder}/receive-items', [PurchaseOrderController::class, 'receiveItems']);
    Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
});

// Reorder Management
Route::controller(ReorderController::class)
    ->prefix('reorder')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get('/', 'index');
        Route::get('/automatic', 'automatic');
        Route::get('/supplier-comparison', 'supplierComparison');
        Route::get('/history', 'history');
        Route::post('/create-po', 'createPurchaseOrder');
        Route::post('/update-points', 'updateReorderPoints');
        Route::post('/clear-cache', 'clearCache');
    });

// Cache Management
Route::controller(App\Http\Controllers\CacheController::class)
    ->prefix('cache')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::post('/clear-all', 'clearAll');
        Route::post('/clear-tags', 'clearByTags');
        Route::post('/clear-reorder', 'clearReorderCache');
        Route::post('/clear-inventory', 'clearInventoryCache');
        Route::post('/clear-products', 'clearProductCache');
        Route::get('/stats', 'stats');
        Route::post('/warmup', 'warmup');
    });

// Temporary test route for exports without auth
Route::post('test-export', [App\Http\Controllers\ReportsAnalyticsController::class, 'exportReport']);
