<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Stock\BulkStockAdjustmentAction;
use App\Actions\Stock\GenerateStockStatisticsAction;
use App\Actions\Stock\ProcessStockMovementAction;
use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\UpdateStockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Services\StockService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockService $stockService,
        private readonly ProcessStockMovementAction $processMovementAction,
        private readonly BulkStockAdjustmentAction $bulkAdjustmentAction,
        private readonly GenerateStockStatisticsAction $generateStatisticsAction
    ) {}

    /**
     * Get available adjustment types and reasons for stock movements.
     */
    public function getAdjustmentTypes(): JsonResponse
    {
        return response()->json([
            'types' => StockMovementType::options(),
            'reasons' => StockMovementReason::options(),
            'reasonsByCategory' => [
                'inbound' => StockMovementReason::byCategory('inbound'),
                'outbound' => StockMovementReason::byCategory('outbound'),
                'loss' => StockMovementReason::byCategory('loss'),
                'adjustment' => StockMovementReason::byCategory('adjustment'),
                'marketing' => StockMovementReason::byCategory('marketing'),
            ],
        ]);
    }

    /**
     * Display a listing of the resource with advanced filtering and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) ($request->get('per_page', 20)), 100);
        $search = $request->get('search', '');
        $type = $request->get('type', '');
        $reason = $request->get('reason', '');
        $storeId = $request->get('store_id', '');
        $productId = $request->get('product_id', '');
        $userId = $request->get('user_id', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        $sortBy = $request->get('sort_by', 'occurred_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $minQuantity = $request->get('min_quantity', '');
        $maxQuantity = $request->get('max_quantity', '');

        $query = StockMovement::with(['product', 'store', 'fromStore', 'toStore', 'user']);

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->whereHas('product', function ($productQuery) use ($search): void {
                    $productQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                })
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by movement type
        if ($type) {
            $query->where('type', $type);
        }

        // Filter by reason
        if ($reason) {
            $query->where('reason', $reason);
        }

        // Filter by store
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        // Filter by product
        if ($productId) {
            $query->where('product_id', $productId);
        }

        // Filter by user
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Filter by date range
        if ($dateFrom) {
            $query->whereDate('occurred_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('occurred_at', '<=', $dateTo);
        }

        // Filter by quantity range
        if ($minQuantity !== '') {
            $query->where('quantity', '>=', (int) $minQuantity);
        }
        if ($maxQuantity !== '') {
            $query->where('quantity', '<=', (int) $maxQuantity);
        }

        // Sorting
        $allowedSorts = ['id', 'occurred_at', 'quantity', 'type', 'reason', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'occurred_at';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

        if ($sortBy === 'product_name') {
            $query->join('products', 'stock_movements.product_id', '=', 'products.id')
                ->orderBy('products.name', $sortOrder)
                ->select('stock_movements.*');
        } elseif ($sortBy === 'store_name') {
            $query->join('stores', 'stock_movements.store_id', '=', 'stores.id')
                ->orderBy('stores.name', $sortOrder)
                ->select('stock_movements.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $movements = $query->paginate($perPage);

        // Add summary statistics
        $stats = [
            'total_movements' => StockMovement::count(),
            'today_movements' => StockMovement::whereDate('occurred_at', today())->count(),
            'additions_today' => StockMovement::where('type', 'addition')->whereDate('occurred_at', today())->sum('quantity'),
            'reductions_today' => StockMovement::where('type', 'reduction')->whereDate('occurred_at', today())->sum('quantity'),
            'transfers_today' => StockMovement::whereIn('type', ['transfer_in', 'transfer_out'])->whereDate('occurred_at', today())->count(),
        ];

        return response()->json([
            'data' => $movements->items(),
            'pagination' => [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
                'from' => $movements->firstItem(),
                'to' => $movements->lastItem(),
            ],
            'filters' => [
                'search' => $search,
                'type' => $type,
                'reason' => $reason,
                'store_id' => $storeId,
                'product_id' => $productId,
                'user_id' => $userId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'min_quantity' => $minQuantity,
                'max_quantity' => $maxQuantity,
            ],
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStockMovementRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id();

            $movement = $this->processMovementAction->execute($data);

            return response()->json($movement, 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create stock movement',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(StockMovement $stockMovement)
    {
        return response()->json($stockMovement->load(['product', 'store', 'fromStore', 'toStore', 'user']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStockMovementRequest $request, StockMovement $stockMovement)
    {
        $stockMovement->update($request->validated());

        return response()->json($stockMovement->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockMovement $stockMovement)
    {
        try {
            DB::transaction(function () use ($stockMovement): void {
                // Reverse the stock adjustment before deleting
                $this->reverseStockAdjustment($stockMovement);

                Log::info('[StockMovement] Deleted stock movement', [
                    'movement_id' => $stockMovement->id,
                    'code' => $stockMovement->code,
                    'type' => $stockMovement->type,
                    'quantity' => $stockMovement->quantity,
                ]);

                $stockMovement->delete();
            });

            return response()->noContent();
        } catch (Exception $e) {
            Log::error('[StockMovement] Failed to delete stock movement', [
                'movement_id' => $stockMovement->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete stock movement',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Handle bulk stock movements
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'movements' => 'required|array|min:1|max:100',
            'movements.*.product_id' => 'required|exists:products,id',
            'movements.*.store_id' => 'required|exists:stores,id',
            'movements.*.type' => 'required|in:addition,reduction,transfer_in,transfer_out,adjustment',
            'movements.*.quantity' => 'required|integer|min:1',
            'movements.*.reason' => 'required|string|max:255',
            'movements.*.notes' => 'nullable|string|max:1000',
            'movements.*.from_store_id' => 'nullable|exists:stores,id',
            'movements.*.to_store_id' => 'nullable|exists:stores,id',
        ]);

        try {
            $movements = $this->bulkAdjustmentAction->execute(
                $request->movements,
                auth()->id()
            );

            return response()->json([
                'message' => 'Bulk stock movements created successfully',
                'movements' => $movements->toArray(),
                'count' => $movements->count(),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to create bulk stock movements',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Get stock movement statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        $stats = $this->generateStatisticsAction->execute(
            $request->date_from,
            $request->date_to,
            $request->store_id
        );

        return response()->json($stats);
    }

    /**
     * Validate stock sufficiency for a product at a store
     */
    public function validateStock(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'store_id' => 'required|integer|exists:stores,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $validation = $this->stockService->validateStockSufficiency(
            $request->integer('product_id'),
            $request->integer('store_id'),
            $request->integer('quantity')
        );

        return response()->json($validation);
    }

    /**
     * Check low stock status for a product at a store
     */
    public function checkLowStock(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'store_id' => 'required|integer|exists:stores,id',
        ]);

        $result = $this->stockService->checkLowStock(
            $request->integer('product_id'),
            $request->integer('store_id')
        );

        return response()->json($result);
    }

    /**
     * Get comprehensive stock summary for a product
     */
    public function productStockSummary(Product $product): JsonResponse
    {
        $summary = $this->stockService->getProductStockSummary($product->id);

        return response()->json($summary);
    }

    /**
     * Validate stock for multiple products/items
     */
    public function validateMultipleStock(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.store_id' => 'required|integer|exists:stores,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $validation = $this->stockService->validateMultipleProducts($request->get('items'));

        return response()->json($validation);
    }

    /**
     * Transfer stock between stores
     */
    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $product = Product::findOrFail($request->product_id);
                $fromStore = Store::findOrFail($request->from_store_id);
                $toStore = Store::findOrFail($request->to_store_id);

                // Check if from store has enough stock
                $fromStockPivot = $product->stores()->where('stores.id', $fromStore->id)->first();
                $currentStock = $fromStockPivot ? $fromStockPivot->pivot->stock : 0;

                if ($currentStock < $request->quantity) {
                    return response()->json([
                        'message' => 'Insufficient stock for transfer',
                        'available_stock' => $currentStock,
                        'requested_quantity' => $request->quantity,
                    ], 422);
                }

                // Create transfer out movement
                $transferOut = StockMovement::create([
                    'code' => 'TXF-OUT-'.date('ymdHis').'-'.random_int(100, 999),
                    'product_id' => $request->product_id,
                    'store_id' => $request->from_store_id,
                    'type' => 'transfer_out',
                    'quantity' => $request->quantity,
                    'reason' => $request->reason ?? 'transfer',
                    'notes' => $request->notes,
                    'from_store_id' => $request->from_store_id,
                    'to_store_id' => $request->to_store_id,
                    'user_id' => auth()->id(),
                    'occurred_at' => now(),
                ]);

                // Create transfer in movement
                $transferIn = StockMovement::create([
                    'code' => 'TXF-IN-'.date('ymdHis').'-'.random_int(100, 999),
                    'product_id' => $request->product_id,
                    'store_id' => $request->to_store_id,
                    'type' => 'transfer_in',
                    'quantity' => $request->quantity,
                    'reason' => $request->reason ?? 'transfer',
                    'notes' => $request->notes,
                    'from_store_id' => $request->from_store_id,
                    'to_store_id' => $request->to_store_id,
                    'user_id' => auth()->id(),
                    'occurred_at' => now(),
                ]);

                // Adjust stock for both stores
                $this->adjustProductStock($transferOut->toArray());
                $this->adjustProductStock($transferIn->toArray());

                Log::info('[StockMovement] Completed stock transfer', [
                    'product_id' => $request->product_id,
                    'from_store_id' => $request->from_store_id,
                    'to_store_id' => $request->to_store_id,
                    'quantity' => $request->quantity,
                    'transfer_out_id' => $transferOut->id,
                    'transfer_in_id' => $transferIn->id,
                ]);

                return response()->json([
                    'message' => 'Stock transfer completed successfully',
                    'transfer_out' => $transferOut->load(['product', 'store', 'fromStore', 'toStore', 'user']),
                    'transfer_in' => $transferIn->load(['product', 'store', 'fromStore', 'toStore', 'user']),
                ], 201);
            });
        } catch (Exception $e) {
            Log::error('[StockMovement] Failed to transfer stock', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'message' => 'Failed to transfer stock',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Adjust product stock based on movement
     */
    private function adjustProductStock(array $data): void
    {
        $product = Product::findOrFail($data['product_id']);
        $store = Store::findOrFail($data['store_id']);

        // Ensure pivot record exists
        $pivot = $product->stores()->where('stores.id', $store->id)->first();
        if (! $pivot) {
            $product->stores()->attach($store->id, ['stock' => 0, 'low_stock_threshold' => 5]);
        }

        // Get current stock
        $currentStock = (int) $product->stores()->where('stores.id', $store->id)->first()->pivot->stock;

        // Calculate new stock based on movement type
        $movementType = StockMovementType::from($data['type']);
        $delta = $movementType->isPositive() ? $data['quantity'] : -$data['quantity'];

        // Handle adjustment type specially
        if ($data['type'] === 'adjustment') {
            // For adjustments, quantity represents the final stock level, not the delta
            $newStock = max(0, (int) $data['quantity']);
        } else {
            $newStock = max(0, $currentStock + $delta);
        }

        // Update the pivot stock
        $product->stores()->updateExistingPivot($store->id, ['stock' => $newStock]);

        Log::debug('[StockMovement] Adjusted product stock', [
            'product_id' => $data['product_id'],
            'store_id' => $data['store_id'],
            'movement_type' => $data['type'],
            'quantity' => $data['quantity'],
            'previous_stock' => $currentStock,
            'new_stock' => $newStock,
            'delta' => $delta,
        ]);
    }

    /**
     * Reverse a stock adjustment (for when deleting a movement)
     */
    private function reverseStockAdjustment(StockMovement $movement): void
    {
        $product = Product::findOrFail($movement->product_id);
        $store = Store::findOrFail($movement->store_id);

        $pivot = $product->stores()->where('stores.id', $store->id)->first();
        if (! $pivot) {
            return; // Nothing to reverse if no pivot exists
        }

        $currentStock = (int) $pivot->pivot->stock;

        // Calculate reverse adjustment
        $movementType = $movement->type instanceof StockMovementType
            ? $movement->type
            : StockMovementType::from($movement->type);
        $reverseDelta = $movementType->isPositive() ? -$movement->quantity : $movement->quantity;

        if ($movement->type === 'adjustment') {
            // For adjustments, we can't easily reverse, so we log a warning
            Log::warning('[StockMovement] Cannot automatically reverse adjustment movement', [
                'movement_id' => $movement->id,
                'current_stock' => $currentStock,
            ]);

            return;
        }

        $newStock = max(0, $currentStock + $reverseDelta);
        $product->stores()->updateExistingPivot($store->id, ['stock' => $newStock]);

        Log::info('[StockMovement] Reversed stock adjustment', [
            'movement_id' => $movement->id,
            'previous_stock' => $currentStock,
            'new_stock' => $newStock,
            'reverse_delta' => $reverseDelta,
        ]);
    }
}
