<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Reorder\CreatePurchaseOrderFromReorderAction;
use App\Actions\Reorder\GenerateReorderListAction;
use App\Services\ReorderService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReorderController extends Controller
{
    public function __construct(
        private readonly ReorderService $reorderService,
        private readonly GenerateReorderListAction $generateReorderListAction,
        private readonly CreatePurchaseOrderFromReorderAction $createPurchaseOrderAction
    ) {}

    /**
     * Get reorder list for a store
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $storeId = (int) $request->get('store_id', 1);
            $groupBy = (string) $request->get('group_by', 'product');

            $data = $this->generateReorderListAction->execute($storeId, $groupBy);

            return response()->json([
                'success' => true,
                'data' => $data['items'],
                'analytics' => $data['analytics'],
                'recommendations' => $data['recommendations'],
                'meta' => $data['meta'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reorder list',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Get automatic reorder suggestions
     */
    public function automatic(Request $request): JsonResponse
    {
        try {
            $storeId = (int) $request->get('store_id', 1);
            $suggestions = $this->reorderService->getAutomaticReorderSuggestions($storeId);

            return response()->json([
                'success' => true,
                'data' => $suggestions,
                'meta' => [
                    'total_items' => $suggestions->count(),
                    'total_estimated_cost' => $suggestions->sum('estimated_cost'),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch automatic reorder suggestions',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Get supplier comparison for reordering
     */
    public function supplierComparison(Request $request): JsonResponse
    {
        try {
            $storeId = (int) $request->get('store_id', 1);
            $comparison = $this->reorderService->getSupplierComparison($storeId);

            return response()->json([
                'success' => true,
                'data' => $comparison,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch supplier comparison',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Get reorder history
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $storeId = (int) $request->get('store_id', 1);
            $days = (int) $request->get('days', 30);

            $history = $this->reorderService->getReorderHistory($storeId, $days);

            return response()->json([
                'success' => true,
                'data' => $history,
                'meta' => [
                    'period_days' => $days,
                    'total_orders' => $history->count(),
                    'total_value' => $history->sum('total_amount'),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reorder history',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Create purchase order from reorder items
     */
    public function createPurchaseOrder(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.supplier_id' => 'required|exists:suppliers,id',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.priority' => 'nullable|integer|between:1,5',
            'items.*.notes' => 'nullable|string|max:500',
            'store_id' => 'required|exists:stores,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->createPurchaseOrderAction->execute(
                $request->items,
                $request->store_id,
                auth()->id(),
                $request->notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Purchase order(s) created successfully',
                'data' => $result['purchase_orders'],
                'summary' => $result['summary'],
                'recommendations' => $result['recommendations'],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase order',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Update reorder points based on sales data
     */
    public function updateReorderPoints(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
        ]);

        try {
            $updatedCount = $this->reorderService->updateReorderPoints($request->store_id);

            return response()->json([
                'success' => true,
                'message' => "Updated reorder points for {$updatedCount} products",
                'data' => [
                    'updated_count' => $updatedCount,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reorder points',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Clear reorder cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
        ]);

        try {
            $this->reorderService->clearReorderCache($request->store_id);

            return response()->json([
                'success' => true,
                'message' => 'Reorder cache cleared successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Get reorder statistics
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $storeId = $request->get('store_id', 1);
            $stats = $this->reorderService->getReorderStats($storeId);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reorder stats',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Get critical reorder items
     */
    public function critical(Request $request): JsonResponse
    {
        try {
            $storeId = $request->get('store_id', 1);
            $critical = $this->reorderService->getCriticalReorderItems($storeId);

            return response()->json([
                'success' => true,
                'data' => $critical,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch critical items',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Get reorder suggestions
     */
    public function suggestions(Request $request): JsonResponse
    {
        try {
            $storeId = $request->get('store_id', 1);
            $suggestions = $this->reorderService->getReorderSuggestions($storeId);

            return response()->json([
                'success' => true,
                'data' => $suggestions,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suggestions',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Get supplier analysis (alias for supplier comparison)
     */
    public function supplierAnalysis(Request $request): JsonResponse
    {
        return $this->supplierComparison($request);
    }
}
