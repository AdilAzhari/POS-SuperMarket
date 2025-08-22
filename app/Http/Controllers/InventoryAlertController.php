<?php

namespace App\Http\Controllers;

use App\Services\InventoryAlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryAlertController extends Controller
{
    public function __construct(
        private readonly InventoryAlertService $inventoryAlertService
    ) {}

    /**
     * Get all low stock alerts
     */
    public function index(): JsonResponse
    {
        $lowStockProducts = $this->inventoryAlertService->getLowStockProducts();
        $criticalStock = $this->inventoryAlertService->getCriticalLowStock();

        return response()->json([
            'success' => true,
            'data' => [
                'low_stock_by_store' => $lowStockProducts,
                'critical_stock' => $criticalStock,
                'summary' => [
                    'total_low_stock_products' => collect($lowStockProducts)->sum(fn ($store) => $store['products']->count()),
                    'critical_products' => $criticalStock->count(),
                    'stores_affected' => count($lowStockProducts),
                ],
            ],
        ]);
    }

    /**
     * Get low stock alerts for a specific store
     */
    public function store(Request $request, int $storeId): JsonResponse
    {
        $lowStock = $this->inventoryAlertService->getLowStockForStore($storeId);

        return response()->json([
            'success' => true,
            'data' => [
                'store_id' => $storeId,
                'products' => $lowStock,
                'count' => $lowStock->count(),
            ],
        ]);
    }

    /**
     * Get critical low stock items (out of stock or very low)
     */
    public function critical(): JsonResponse
    {
        $criticalStock = $this->inventoryAlertService->getCriticalLowStock();

        return response()->json([
            'success' => true,
            'data' => [
                'critical_items' => $criticalStock,
                'count' => $criticalStock->count(),
            ],
        ]);
    }

    /**
     * Get reorder suggestions for a store
     */
    public function reorderSuggestions(int $storeId): JsonResponse
    {
        $suggestions = $this->inventoryAlertService->generateReorderSuggestions($storeId);

        return response()->json([
            'success' => true,
            'data' => [
                'store_id' => $storeId,
                'suggestions' => $suggestions,
                'total_estimated_cost' => $suggestions->sum('estimated_cost'),
                'high_priority_count' => $suggestions->where('priority', '>=', 4)->count(),
            ],
        ]);
    }

    /**
     * Send low stock alerts manually
     */
    public function sendAlerts(): JsonResponse
    {
        $alertsSent = $this->inventoryAlertService->sendLowStockAlerts();

        return response()->json([
            'success' => true,
            'message' => "Low stock alerts sent to {$alertsSent} managers",
            'alerts_sent' => $alertsSent,
        ]);
    }

    /**
     * Update optimal stock thresholds based on sales patterns
     */
    public function updateThresholds(int $storeId): JsonResponse
    {
        $updated = $this->inventoryAlertService->updateOptimalThresholds($storeId);

        return response()->json([
            'success' => true,
            'message' => "Updated {$updated} product thresholds based on sales patterns",
            'products_updated' => $updated,
        ]);
    }

    /**
     * Get inventory analytics dashboard
     */
    public function dashboard(Request $request): JsonResponse
    {
        $storeId = $request->get('store_id');

        if ($storeId) {
            $lowStock = $this->inventoryAlertService->getLowStockForStore($storeId);
            $suggestions = $this->inventoryAlertService->generateReorderSuggestions($storeId);

            $data = [
                'store_id' => $storeId,
                'low_stock_products' => $lowStock->count(),
                'critical_products' => $lowStock->where('severity', '>=', 4)->count(),
                'total_reorder_cost' => $suggestions->sum('estimated_cost'),
                'products_needing_attention' => $lowStock->take(10),
                'top_reorder_suggestions' => $suggestions->take(10),
            ];
        } else {
            $allLowStock = $this->inventoryAlertService->getLowStockProducts();
            $critical = $this->inventoryAlertService->getCriticalLowStock();

            $data = [
                'total_stores_affected' => count($allLowStock),
                'total_low_stock_products' => collect($allLowStock)->sum(fn ($store) => $store['products']->count()),
                'total_critical_products' => $critical->count(),
                'out_of_stock_products' => $critical->where('is_out_of_stock', true)->count(),
                'stores_summary' => collect($allLowStock)->map(fn ($store) => [
                    'store_name' => $store['store']->name,
                    'low_stock_count' => $store['products']->count(),
                    'critical_count' => $store['products']->where('severity', '>=', 4)->count(),
                ]),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Clear inventory alerts cache
     */
    public function clearCache(): JsonResponse
    {
        $this->inventoryAlertService->clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Inventory alerts cache cleared successfully',
        ]);
    }

    /**
     * Get low stock products for display in POS interface
     */
    public function posAlerts(int $storeId): JsonResponse
    {
        $lowStock = $this->inventoryAlertService->getLowStockForStore($storeId);

        // Return simplified data for POS interface
        $alerts = $lowStock->where('severity', '>=', 3)->map(function ($item) {
            return [
                'id' => $item['product']->id,
                'name' => $item['product']->name,
                'sku' => $item['product']->sku,
                'current_stock' => $item['current_stock'],
                'threshold' => $item['threshold'],
                'severity' => $item['severity'],
                'is_critical' => $item['severity'] >= 4,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'alerts' => $alerts,
                'count' => $alerts->count(),
                'has_critical' => $alerts->where('is_critical', true)->isNotEmpty(),
            ],
        ]);
    }
}
