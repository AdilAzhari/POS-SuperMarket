<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\UpdateStockMovementRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
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
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            StockMovement::with(['product', 'store', 'fromStore', 'toStore', 'user'])
                ->orderByDesc('occurred_at')
                ->paginate(20)
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStockMovementRequest $request)
    {
        try {
            $data = $request->validated();

            return DB::transaction(function () use ($data) {
                $movement = StockMovement::create($data);

                // Adjust pivot stock for product-store
                $product = Product::findOrFail($data['product_id']);
                $store = Store::findOrFail($data['store_id']);
                $pivot = $product->stores()->where('stores.id', $store->id)->first();
                if (! $pivot) {
                    $product->stores()->attach($store->id, ['stock' => 0, 'low_stock_threshold' => 0]);
                }

                $currentStock = (int) $product->stores()->where('stores.id', $store->id)->first()->pivot->stock;
                $movementType = StockMovementType::from($data['type']);
                $delta = $movementType->isPositive() ? $data['quantity'] : -$data['quantity'];
                $newStock = max(0, $currentStock + $delta);
                $product->stores()->updateExistingPivot($store->id, ['stock' => $newStock]);

                return response()->json($movement->load(['product', 'store']), 201);
            });
        } catch (\Exception $e) {
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
     * Show the form for editing the specified resource.
     */
    public function edit(StockMovement $stockMovement)
    {
        //
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
        $stockMovement->delete();

        return response()->noContent();
    }
}
