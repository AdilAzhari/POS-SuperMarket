<?php

namespace App\Http\Controllers;

use App\DTOs\CreateSaleDTO;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\SaleProcessingException;
use App\Http\Requests\StoreSaleRequest;
use App\Services\SaleService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) ($request->get('per_page', 20));
        $sales = $this->saleService->getPaginatedSales($perPage);

        return response()->json($sales);
    }

    /**
     * @throws InsufficientStockException
     * @throws SaleProcessingException
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $saleDTO = new CreateSaleDTO(
                store_id: $data['store_id'],
                cashier_id: $data['cashier_id'],
                customer_id: $data['customer_id'] ?? null,
                items: $data['items'],
                payment_method: $data['payment_method'],
                discount: (float) ($data['discount'] ?? 0),
                tax: (float) ($data['tax'] ?? 0),
                paid_at: $data['paid_at'] ?? null
            );

            $sale = $this->saleService->createSale($saleDTO);

            return response()->json($sale, 201);
        } catch (InsufficientStockException $e) {
            return $e->render();
        } catch (SaleProcessingException $e) {
            if (str_contains($e->getMessage(), 'Insufficient stock')) {
                return response()->json(['error' => 'Insufficient Stock', 'message' => $e->getMessage()], 422);
            }

            return response()->json(['error' => 'Sale Processing Failed', 'message' => $e->getMessage()], 422);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'Insufficient stock')) {
                return response()->json(['error' => 'Insufficient Stock', 'message' => $e->getMessage()], 422);
            }
            throw $e;
        }
    }

    public function show(string $id): JsonResponse
    {
        $sale = $this->saleService->getSaleById((int) $id);

        return response()->json($sale);
    }

    public function update(): JsonResponse
    {
        return response()->json(['message' => 'Sales cannot be modified after creation'], 422);
    }

    public function destroy(): JsonResponse
    {
        // For POS systems, sales should not be deleted, only voided
        return response()->json(['message' => 'Sales cannot be deleted. Use void endpoint instead'], 422);
    }
}
