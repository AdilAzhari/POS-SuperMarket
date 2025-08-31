<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorestoreRequest;
use App\Http\Requests\UpdatestoreRequest;
use App\Models\Store;
use App\Services\StoreService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class StoreController extends Controller
{
    public function __construct(
        private readonly StoreService $storeService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'with_contact']);
            $stores = $this->storeService->getAllStores($filters);

            // Add computed attributes for each store
            $storesWithData = $stores->map(fn ($store): array => [
                'id' => $store->id,
                'name' => $store->name,
                'address' => $store->address,
                'phone' => $store->phone,
                'email' => $store->email,
                'created_at' => $store->created_at,
                'updated_at' => $store->updated_at,
                'total_products' => $store->products()->count(),
                'total_stock' => $store->products()->sum('stock') ?? 0,
                'total_sales_amount' => $store->sales()->sum('total') ?? 0,
                'has_contact' => $store->hasContact(),
            ]);

            return response()->json([
                'data' => $storesWithData,
                'total' => $stores->count(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch stores',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorestoreRequest $request): JsonResponse
    {
        try {
            $store = $this->storeService->createStore($request->validated());

            return response()->json([
                'data' => $store,
                'message' => 'Store created successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to create store',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store): JsonResponse
    {
        try {
            $storeDetails = $this->storeService->getStoreDetails($store);

            return response()->json([
                'data' => $storeDetails,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch store details',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatestoreRequest $request, Store $store): JsonResponse
    {
        try {
            $updatedStore = $this->storeService->updateStore($store, $request->validated());

            return response()->json([
                'data' => $updatedStore,
                'message' => 'Store updated successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to update store',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store): JsonResponse
    {
        try {
            $this->storeService->deleteStore($store);

            return response()->json([
                'message' => 'Store deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to delete store',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get store analytics
     */
    public function analytics(): JsonResponse
    {
        try {
            $analytics = $this->storeService->getStoresAnalytics();

            return response()->json([
                'data' => $analytics,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch analytics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate store for sales operations
     */
    public function validate(Store $store): JsonResponse
    {
        try {
            $issues = $this->storeService->validateStoreForSale($store);

            return response()->json([
                'valid' => $issues === [],
                'issues' => $issues,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to validate store',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
