<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Common\FormatApiResponseAction;
use App\Actions\Common\HandleControllerErrorsAction;
use App\Actions\Common\HandleValidatedRequestAction;
use App\Services\InventoryAlertService;
use App\Services\ReorderService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class InventoryAlertController extends Controller
{
    public function __construct(
        private readonly InventoryAlertService $inventoryAlertService,
        private readonly ReorderService $reorderService,
        private readonly HandleValidatedRequestAction $validationHandler,
        private readonly FormatApiResponseAction $responseFormatter,
        private readonly HandleControllerErrorsAction $errorHandler
    ) {}

    /**
     * Get all low stock alerts
     */
    public function index(): JsonResponse
    {
        try {
            $lowStockProducts = $this->inventoryAlertService->getLowStockProducts();
            $criticalStock = $this->inventoryAlertService->getCriticalLowStock();

            return $this->responseFormatter->collection([
                'low_stock_by_store' => $lowStockProducts,
                'critical_stock' => $criticalStock,
                'summary' => [
                    'total_low_stock_products' => collect($lowStockProducts)->sum(fn ($store) => $store['products']->count()),
                    'critical_products' => $criticalStock->count(),
                    'stores_affected' => count($lowStockProducts),
                ],
            ]);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'inventory alert listing');
        }
    }

    /**
     * Get low stock alerts for a specific store
     */
    public function store(Request $request, int $storeId): JsonResponse
    {
        try {
            $lowStock = $this->inventoryAlertService->getLowStockForStore($storeId);

            return $this->responseFormatter->collection([
                'store_id' => $storeId,
                'products' => $lowStock,
                'count' => $lowStock->count(),
            ]);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'store inventory alerts');
        }
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
        try {
            $validated = $this->validationHandler->validateStoreContext($request);
            $storeId = $validated['store_id'] ?? null;

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
                    'stores_summary' => collect($allLowStock)->map(fn ($store): array => [
                        'store_name' => $store['store']->name,
                        'low_stock_count' => $store['products']->count(),
                        'critical_count' => $store['products']->where('severity', '>=', 4)->count(),
                    ]),
                ];
            }

            return $this->responseFormatter->dashboard($data);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'inventory dashboard');
        }
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
        $alerts = $lowStock->where('severity', '>=', 3)->map(fn (array $item): array => [
            'id' => $item['product']->id,
            'name' => $item['product']->name,
            'sku' => $item['product']->sku,
            'current_stock' => $item['current_stock'],
            'threshold' => $item['threshold'],
            'severity' => $item['severity'],
            'is_critical' => $item['severity'] >= 4,
        ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'alerts' => $alerts,
                'count' => $alerts->count(),
                'has_critical' => $alerts->where('is_critical', true)->isNotEmpty(),
            ],
        ]);
    }

    /**
     * Get automatic reorder recommendations for critical items
     */
    public function automaticReorderRecommendations(int $storeId): JsonResponse
    {
        $automaticReorders = $this->reorderService->getAutomaticReorderSuggestions($storeId);

        return response()->json([
            'success' => true,
            'data' => [
                'store_id' => $storeId,
                'automatic_reorders' => $automaticReorders,
                'count' => $automaticReorders->count(),
                'total_cost' => $automaticReorders->sum('estimated_cost'),
                'message' => $automaticReorders->count() > 0
                    ? "Found {$automaticReorders->count()} products that should be reordered immediately"
                    : 'No automatic reorders needed at this time',
            ],
        ]);
    }

    /**
     * Get integration data for inventory alerts with reorder system
     */
    public function reorderIntegration(int $storeId): JsonResponse
    {
        $lowStock = $this->inventoryAlertService->getLowStockForStore($storeId);
        $reorderList = $this->reorderService->getReorderList($storeId);
        $automaticReorders = $this->reorderService->getAutomaticReorderSuggestions($storeId);

        return response()->json([
            'success' => true,
            'data' => [
                'store_id' => $storeId,
                'low_stock_count' => $lowStock->count(),
                'reorder_ready_count' => $reorderList->count(),
                'automatic_reorder_count' => $automaticReorders->count(),
                'total_reorder_cost' => $reorderList->sum('estimated_cost'),
                'critical_items_needing_immediate_action' => $automaticReorders->take(5),
                'reorder_summary_by_supplier' => $this->reorderService->getReorderListBySupplier($storeId)->take(3),
            ],
        ]);
    }
}
