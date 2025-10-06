<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\PurchaseOrder\CreatePurchaseOrderAction;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\ReorderService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly ReorderService $reorderService,
        private readonly CreatePurchaseOrderAction $createPurchaseOrderAction
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = PurchaseOrder::query()
                ->with(['supplier', 'store', 'createdBy', 'items.product']);

            // Filter by store
            if ($request->has('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            // Filter by supplier
            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search): void {
                    $q->where('po_number', 'LIKE', "%$search%")
                        ->orWhereHas('supplier', function ($sq) use ($search): void {
                            $sq->where('name', 'LIKE', "%$search%");
                        });
                });
            }

            $purchaseOrders = $query->latest()->paginate($request->get('per_page', 20));

            // Add progress information to each PO
            $purchaseOrders->getCollection()->transform(function ($po) {
                $po->progress = $po->getProgress();

                return $po;
            });

            return response()->json([
                'success' => true,
                'data' => $purchaseOrders,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch purchase orders',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'store_id' => 'required|exists:stores,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'expected_delivery_at' => 'nullable|date|after:today',
        ]);

        try {
            $purchaseOrder = $this->createPurchaseOrderAction->execute(
                $request->validated(),
                Auth::check() ? Auth::id() : 1 // Default to user ID 1 if not authenticated
            );

            return response()->json([
                'success' => true,
                'message' => 'Purchase order created successfully',
                'data' => $purchaseOrder,
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
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            $purchaseOrder->load([
                'supplier',
                'store',
                'createdBy',
                'items.product.category',
            ]);

            $purchaseOrder->progress = $purchaseOrder->getProgress();

            return response()->json([
                'success' => true,
                'data' => $purchaseOrder,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch purchase order',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if (! $purchaseOrder->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order cannot be edited in current status',
            ], 422);
        }

        $request->validate([
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'items' => 'sometimes|array|min:1',
            'items.*.id' => 'sometimes|exists:purchase_order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'expected_delivery_at' => 'nullable|date|after:today',
        ]);

        try {
            // Update purchase order
            $purchaseOrder->update($request->only([
                'supplier_id', 'notes', 'expected_delivery_at',
            ]));

            // Update items if provided
            if ($request->has('items')) {
                // Delete existing items
                $purchaseOrder->items()->delete();

                $totalAmount = 0;

                foreach ($request->items as $item) {
                    $totalCost = $item['quantity_ordered'] * $item['unit_cost'];

                    PurchaseOrderItem::query()->create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_id' => $item['product_id'],
                        'quantity_ordered' => $item['quantity_ordered'],
                        'unit_cost' => $item['unit_cost'],
                        'total_cost' => $totalCost,
                        'notes' => $item['notes'] ?? null,
                    ]);

                    $totalAmount += $totalCost;
                }

                $purchaseOrder->update(['total_amount' => $totalAmount]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Purchase order updated successfully',
                'data' => $purchaseOrder->fresh()->load(['supplier', 'store', 'items.product']),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase order',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder): JsonResponse
    {
        if (! $purchaseOrder->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order cannot be deleted in current status',
            ], 422);
        }

        try {
            $purchaseOrder->delete();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete purchase order',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Mark purchase order as ordered
     */
    public function markOrdered(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            if (! in_array($purchaseOrder->status, ['draft', 'pending'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase order cannot be marked as ordered in current status',
                ], 422);
            }

            $purchaseOrder->markAsOrdered();

            return response()->json([
                'success' => true,
                'message' => 'Purchase order marked as ordered',
                'data' => $purchaseOrder->fresh(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase order status',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Receive items from purchase order
     */
    public function receiveItems(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        if (! $purchaseOrder->canBeReceived()) {
            return response()->json([
                'success' => false,
                'message' => 'Purchase order cannot receive items in current status',
            ], 422);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|integer|min:0',
        ]);

        try {
            $updatedItems = 0;

            foreach ($request->items as $itemData) {
                $item = PurchaseOrderItem::query()->find($itemData['id']);

                if ($item && $item->purchase_order_id === $purchaseOrder->id) {
                    $newQuantity = min(
                        $itemData['quantity_received'],
                        $item->quantity_ordered
                    );

                    $item->update(['quantity_received' => $newQuantity]);
                    $updatedItems++;

                    // Update stock in the store
                    $product = $item->product;
                    $store = $purchaseOrder->store;

                    // Add to stock
                    $existingStock = $product->stores()->where('stores.id', $store->id)->first();
                    if ($existingStock) {
                        $newStock = $existingStock->pivot->stock + $newQuantity;
                        $product->stores()->updateExistingPivot($store->id, [
                            'stock' => $newStock,
                        ]);
                    }
                }
            }

            // Update purchase order status
            $purchaseOrder->markAsReceived();

            // Clear reorder cache
            $this->reorderService->clearReorderCache($purchaseOrder->store_id);

            return response()->json([
                'success' => true,
                'message' => "Received items for {$updatedItems} products",
                'data' => $purchaseOrder->fresh()->load(['items.product']),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to receive items',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }

    /**
     * Cancel purchase order
     */
    public function cancel(PurchaseOrder $purchaseOrder): JsonResponse
    {
        try {
            if (in_array($purchaseOrder->status, ['received', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Purchase order cannot be cancelled in current status',
                ], 422);
            }

            $purchaseOrder->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Purchase order cancelled successfully',
                'data' => $purchaseOrder->fresh(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel purchase order',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while processing your request.',
            ], 500);
        }
    }
}
