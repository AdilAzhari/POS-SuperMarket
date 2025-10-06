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
use App\Services\ProductService;
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

// Products - Admin functions (authentication handled at frontend level)
Route::prefix('products')->group(function (): void {
    Route::get('search', [ProductController::class, 'search']);
    Route::get('low-stock', [ProductController::class, 'lowStock']);
});
Route::apiResource('products', ProductController::class);

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

// Purchase Orders - Admin functions (authentication handled at frontend level)
Route::post('purchase-orders/{purchaseOrder}/mark-ordered', [PurchaseOrderController::class, 'markOrdered']);
Route::post('purchase-orders/{purchaseOrder}/receive-items', [PurchaseOrderController::class, 'receiveItems']);
Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
Route::apiResource('purchase-orders', PurchaseOrderController::class);

// Reorder Management - Admin functions (authentication handled at frontend level)
Route::controller(ReorderController::class)
    ->prefix('reorder')
    ->group(function (): void {
        Route::get('/', 'index');
        Route::get('/stats', 'stats');
        Route::get('/critical', 'critical');
        Route::get('/suggestions', 'suggestions');
        Route::get('/automatic', 'automatic');
        Route::get('/supplier-comparison', 'supplierComparison');
        Route::get('/supplier-analysis', 'supplierAnalysis');
        Route::get('/history', 'history');
        Route::post('/create-po', 'createPurchaseOrder');
        Route::post('/update-points', 'updateReorderPoints');
        Route::post('/clear-cache', 'clearCache');
    });

// Cache Management - Admin functions (authentication handled at frontend level)
Route::controller(App\Http\Controllers\CacheController::class)
    ->prefix('cache')
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

// Test route to check if basic routing works
Route::get('test-products', function () {
    return response()->json(['message' => 'Test route works', 'timestamp' => now()]);
});

// Test direct product access without controller actions
Route::get('test-products-direct', function () {
    $products = App\Models\Product::with(['category', 'supplier', 'stores'])->paginate(5);

    return response()->json([
        'data' => $products->items(),
        'current_page' => $products->currentPage(),
        'last_page' => $products->lastPage(),
        'per_page' => $products->perPage(),
        'total' => $products->total(),
        'from' => $products->firstItem(),
        'to' => $products->lastItem(),
    ]);
});

// Override the products.index route temporarily
Route::get('products-simple', function (Request $request) {
    $perPage = (int) ($request->get('per_page', 20));
    $products = App\Models\Product::with(['category', 'supplier', 'stores'])->paginate($perPage);

    return response()->json([
        'data' => $products->items(),
        'current_page' => $products->currentPage(),
        'last_page' => $products->lastPage(),
        'per_page' => $products->perPage(),
        'total' => $products->total(),
        'from' => $products->firstItem(),
        'to' => $products->lastItem(),
    ]);
});

// Test ProductService directly
Route::get('products-service-test', function (Request $request) {
    $perPage = (int) ($request->get('per_page', 20));
    $service = new ProductService();
    $products = $service->getPaginatedProducts($perPage);

    return response()->json([
        'data' => $products->items(),
        'current_page' => $products->currentPage(),
        'last_page' => $products->lastPage(),
        'per_page' => $products->perPage(),
        'total' => $products->total(),
        'from' => $products->firstItem(),
        'to' => $products->lastItem(),
    ]);
});

// Working product store route
Route::post('products-store-test', function (Request $request) {
    // Basic validation rules from StoreProductRequest
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'sku' => 'required|string|max:255|unique:products',
        'price' => 'required|numeric|min:0',
        'cost' => 'nullable|numeric|min:0',
        'active' => 'sometimes|boolean',
        'category_id' => 'nullable|exists:categories,id',
        'supplier_id' => 'nullable|exists:suppliers,id',
        'barcode' => 'nullable|string|unique:products',
        'low_stock_threshold' => 'nullable|integer|min:0',
    ]);

    try {
        $service = new ProductService();
        $product = $service->createProduct($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data' => $product,
        ], 201);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to create product',
            'error' => $e->getMessage(),
        ], 500);
    }
});

// Working product update route
Route::put('products-update-test/{id}', function (Request $request, $id) {
    // Validation rules for updates (most fields optional)
    $validated = $request->validate([
        'name' => 'sometimes|string|max:255',
        'sku' => 'sometimes|string|max:255|unique:products,sku,'.$id,
        'price' => 'sometimes|numeric|min:0',
        'cost' => 'nullable|numeric|min:0',
        'active' => 'sometimes|boolean',
        'category_id' => 'nullable|exists:categories,id',
        'supplier_id' => 'nullable|exists:suppliers,id',
        'barcode' => 'nullable|string|unique:products,barcode,'.$id,
        'low_stock_threshold' => 'nullable|integer|min:0',
    ]);

    try {
        $service = new ProductService();
        $product = $service->updateProduct((int) $id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data' => $product,
        ]);
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update product',
            'error' => $e->getMessage(),
        ], 500);
    }
});
