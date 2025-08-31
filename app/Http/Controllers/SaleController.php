<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Common\FormatApiResponseAction;
use App\Actions\Common\HandleControllerErrorsAction;
use App\Actions\Common\HandleValidatedRequestAction;
use App\Actions\Sale\ProcessSaleAction;
use App\DTOs\CreateSaleDTO;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\SaleProcessingException;
use App\Http\Requests\StoreSaleRequest;
use App\Services\SaleService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SaleController extends Controller
{
    public function __construct(
        private readonly SaleService $saleService,
        private readonly ProcessSaleAction $processSaleAction,
        private readonly HandleValidatedRequestAction $validationHandler,
        private readonly FormatApiResponseAction $responseFormatter,
        private readonly HandleControllerErrorsAction $errorHandler
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->validatePagination($request);
            $perPage = $validated['per_page'] ?? 20;
            $sales = $this->saleService->getPaginatedSales($perPage);

            return $this->responseFormatter->paginated($sales);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'sale listing');
        }
    }

    /**
     * @throws InsufficientStockException
     * @throws SaleProcessingException
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        try {
            $userData = $this->validationHandler->execute($request);

            $saleDTO = new CreateSaleDTO(
                store_id: $userData['store_id'],
                cashier_id: $userData['cashier_id'],
                customer_id: $userData['customer_id'] ?? null,
                items: $userData['items'],
                payment_method: $userData['payment_method'],
                discount: (float) ($userData['discount'] ?? 0),
                tax: (float) ($userData['tax'] ?? 0),
                loyalty_reward_id: $userData['loyalty_reward_id'] ?? null,
                loyalty_discount: (float) ($userData['loyalty_discount'] ?? 0),
                paid_at: $userData['paid_at'] ?? null
            );

            $sale = $this->processSaleAction->execute($saleDTO);

            return $this->responseFormatter->created($sale, 'Sale processed successfully');
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
        try {
            $sale = $this->saleService->getSaleById((int) $id);

            return $this->responseFormatter->resource($sale);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'sale retrieval');
        }
    }

    public function update(): JsonResponse
    {
        return response()->json(['message' => 'Sales cannot be modified after creation'], 422);
    }

    public function destroy(): JsonResponse
    {
        // For POS systems, sales should not be deleted, only voided
        return response()->json([
            'error' => 'Operation Not Allowed',
            'message' => 'Sales cannot be deleted. Use void endpoint instead',
        ], 422);
    }
}
