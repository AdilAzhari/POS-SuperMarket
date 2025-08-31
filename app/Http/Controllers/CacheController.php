<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReorderService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class CacheController extends Controller
{
    public function __construct(
        private readonly ReorderService $reorderService
    ) {}

    /**
     * Clear all application cache
     */
    public function clearAll(): JsonResponse
    {
        try {
            Cache::flush();

            return response()->json([
                'success' => true,
                'message' => 'All cache cleared successfully',
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
     * Clear cache by tags
     */
    public function clearByTags(Request $request): JsonResponse
    {
        $request->validate([
            'tags' => 'required|array|min:1',
            'tags.*' => 'required|string',
        ]);

        try {
            $driver = config('cache.default');
            if (! in_array($driver, ['redis', 'memcached', 'array'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cache driver '$driver' does not support tagging. Please use Redis, Memcached, or Array driver for tag-based cache clearing.",
                ], 422);
            }

            Cache::tags($request->tags)->flush();

            $tagsList = implode(', ', $request->tags);

            return response()->json([
                'success' => true,
                'message' => "Cache cleared for tags: $tagsList",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache by tags',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Clear reorder cache for specific store
     */
    public function clearReorderCache(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'sometimes|exists:stores,id',
        ]);

        try {
            if ($request->has('store_id')) {
                $this->reorderService->clearReorderCache($request->store_id);
                $message = "Reorder cache cleared for store {$request->store_id}";
            } else {
                $this->reorderService->clearAllReorderCache();
                $message = 'All reorder cache cleared';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear reorder cache',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Clear inventory cache
     */
    public function clearInventoryCache(): JsonResponse
    {
        try {
            $driver = config('cache.default');
            if (in_array($driver, ['redis', 'memcached', 'array'])) {
                Cache::tags(['inventory', 'alerts'])->flush();
            } else {
                // For drivers that don't support tagging, clear all cache
                Cache::flush();
            }

            return response()->json([
                'success' => true,
                'message' => 'Inventory cache cleared successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear inventory cache',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Clear product cache
     */
    public function clearProductCache(): JsonResponse
    {
        try {
            $driver = config('cache.default');
            if (in_array($driver, ['redis', 'memcached', 'array'])) {
                Cache::tags(['products'])->flush();
            } else {
                // For drivers that don't support tagging, clear all cache
                Cache::flush();
            }

            return response()->json([
                'success' => true,
                'message' => 'Product cache cleared successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear product cache',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Get cache statistics and info
     */
    public function stats(): JsonResponse
    {
        try {
            $cacheStats = [
                'available_cache_drivers' => config('cache.stores'),
                'current_driver' => config('cache.default'),
                'tagged_cache_supported' => in_array(config('cache.default'), ['redis', 'memcached', 'array']),
                'common_cache_tags' => [
                    'reorder' => 'Reorder management cache',
                    'inventory' => 'Inventory and alerts cache',
                    'products' => 'Product data cache',
                    'sales' => 'Sales data cache',
                    'reports' => 'Report generation cache',
                    'analytics' => 'Analytics data cache',
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $cacheStats,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve cache statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Warm up critical cache
     */
    public function warmup(Request $request): JsonResponse
    {
        $request->validate([
            'store_id' => 'sometimes|exists:stores,id',
        ]);

        try {
            $storeId = $request->get('store_id', 1);
            $warmedItems = [];

            // Warm up reorder cache
            $this->reorderService->getReorderList($storeId);
            $warmedItems[] = 'Reorder list';

            $this->reorderService->getAutomaticReorderSuggestions($storeId);
            $warmedItems[] = 'Auto-reorder suggestions';

            $this->reorderService->getSupplierComparison($storeId);
            $warmedItems[] = 'Supplier comparison';

            return response()->json([
                'success' => true,
                'message' => 'Cache warmed up successfully',
                'warmed_items' => $warmedItems,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to warm up cache',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }
}
