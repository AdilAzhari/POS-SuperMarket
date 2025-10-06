<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Common\FormatApiResponseAction;
use App\Actions\Common\HandleControllerErrorsAction;
use App\Actions\Common\HandleValidatedRequestAction;
use App\Http\Requests\StoreProductRequest;
use App\Services\ProductService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly HandleValidatedRequestAction $validationHandler,
        private readonly FormatApiResponseAction $responseFormatter,
        private readonly HandleControllerErrorsAction $errorHandler
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->validatePagination($request);
            $perPage = (int) ($validated['per_page'] ?? 20);
            $products = $this->productService->getPaginatedProducts($perPage);

            return $this->responseFormatter->paginated($products);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'product listing');
        }
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            \Illuminate\Log\log()->info('here', $request->all());
            $validated = $this->validationHandler->execute($request);
            $product = $this->productService->createProduct($validated);

            return $this->responseFormatter->created($product, 'Product created successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'product creation');
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->productService->getProductById((int) $id);

            return $this->responseFormatter->resource($product);
        } catch (ModelNotFoundException) {
            return response()->json(['error' => 'Product not found'], 404);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'product retrieval');
        }
    }

    public function update(StoreProductRequest $request, string $id): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request);
            $product = $this->productService->updateProduct((int) $id, $validated);

            return $this->responseFormatter->updated($product, 'Product updated successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'product update');
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $this->productService->deleteProduct((int) $id);

            return $this->responseFormatter->deleted('Product deleted successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'product deletion');
        }
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $barcode = (string) $request->string('barcode', '');

            // If barcode is provided, search by barcode specifically
            if ($barcode) {
                $product = $this->productService->findByBarcode($barcode);
                if ($product instanceof \App\Models\Product) {
                    return $this->responseFormatter->resource($product, 'Product found by barcode');
                }

                return response()->json([
                    'error' => 'Product not found',
                    'message' => "No product found with barcode: $barcode",
                ], 404);
            }

            // Otherwise, use regular search
            $products = $this->productService->searchProducts($query);

            return $this->responseFormatter->collection($products);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'product search');
        }
    }

    public function lowStock(Request $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->validateStoreContext($request);
            $storeId = $validated['store_id'] ?? 1;
            $products = $this->productService->getLowStockProducts($storeId);

            return $this->responseFormatter->collection($products, [], 'Low stock products retrieved');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'low stock retrieval');
        }
    }
}
