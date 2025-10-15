<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Common\FormatApiResponseAction;
use App\Actions\Common\HandleControllerErrorsAction;
use App\Actions\Common\HandleValidatedRequestAction;
use App\Actions\ProductReturn\ProcessReturnAction;
use App\DTOs\ProductReturnDTO;
use App\Http\Requests\StoreProductReturnRequest;
use App\Models\ProductReturn;
use App\Models\Sale;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductReturnController extends Controller
{
    public function __construct(
        private readonly ProcessReturnAction $processReturnAction,
        private readonly HandleValidatedRequestAction $validationHandler,
        private readonly FormatApiResponseAction $responseFormatter,
        private readonly HandleControllerErrorsAction $errorHandler
    ) {}

    /**
     * Display a listing of returns
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->validatePagination($request);
            $perPage = $validated['per_page'] ?? 20;

            $returns = ProductReturn::query()
                ->with(['sale', 'customer', 'processedBy', 'items'])
                ->latest()
                ->paginate($perPage);

            return $this->responseFormatter->paginated($returns);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'returns listing');
        }
    }

    /**
     * Store a newly created return
     */
    public function store(StoreProductReturnRequest $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request);

            // Get sale to retrieve store_id and customer_id
            $sale = Sale::with('customer')->findOrFail($validated['sale_id']);

            // Create return DTO
            $returnDTO = new ProductReturnDTO(
                sale_id: $validated['sale_id'],
                store_id: $sale->store_id,
                processed_by: $validated['processed_by'] ?? auth()->id() ?? 1,
                reason: $validated['reason'],
                refund_method: $validated['refund_method'],
                subtotal: 0, // Will be calculated in action
                tax_refund: 0, // Will be calculated in action
                total_refund: 0, // Will be calculated in action
                customer_id: $sale->customer_id,
                notes: $validated['notes'] ?? null,
            );

            // Process the return
            $return = $this->processReturnAction->execute($returnDTO, $validated['items']);

            return $this->responseFormatter->created($return, 'Return processed successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'return processing');
        }
    }

    /**
     * Display the specified return
     */
    public function show(string $id): JsonResponse
    {
        try {
            $return = ProductReturn::query()
                ->with(['sale', 'customer', 'processedBy', 'items.product', 'items.saleItem'])
                ->findOrFail($id);

            return $this->responseFormatter->resource($return);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'return retrieval');
        }
    }

    /**
     * Update the specified return (limited updates allowed)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $return = ProductReturn::findOrFail($id);

            // Only allow status and notes updates
            $validated = $request->validate([
                'status' => 'sometimes|in:pending,approved,rejected,completed',
                'notes' => 'sometimes|nullable|string',
            ]);

            $return->update($validated);

            return $this->responseFormatter->resource($return, 'Return updated successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'return update');
        }
    }

    /**
     * Remove the specified return (soft operations only)
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $return = ProductReturn::findOrFail($id);

            // Only allow deletion if status is pending
            if ($return->status !== 'pending') {
                return response()->json([
                    'error' => 'Invalid Operation',
                    'message' => 'Cannot delete returns that have been processed',
                ], 422);
            }

            $return->delete();

            return $this->responseFormatter->deleted('Return cancelled successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'return deletion');
        }
    }
}
