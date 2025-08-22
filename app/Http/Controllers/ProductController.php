<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) ($request->get('per_page', 20));
        $products = $this->productService->getPaginatedProducts($perPage);

        return response()->json($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());

        return response()->json($product, 201);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->productService->getProductById((int) $id);

            return response()->json($product);
        } catch (ModelNotFoundException) {
            return response()->json(['error' => 'Product not found'], 404);
        }
    }

    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = $this->productService->updateProduct((int) $id, $request->validated());

        return response()->json($product);
    }

    public function destroy(string $id): Response
    {
        $this->productService->deleteProduct((int) $id);

        return response()->noContent();
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $barcode = $request->get('barcode', '');

        // If barcode is provided, search by barcode specifically
        if ($barcode) {
            $product = $this->productService->findByBarcode($barcode);
            if ($product) {
                return response()->json([
                    'data' => $product,
                    'found_by' => 'barcode',
                ]);
            } else {
                return response()->json([
                    'error' => 'Product not found',
                    'message' => "No product found with barcode: $barcode",
                ], 404);
            }
        }

        // Otherwise, use regular search
        $products = $this->productService->searchProducts($query);

        return response()->json($products);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $storeId = (int) $request->get('store_id', 1);
        $products = $this->productService->getLowStockProducts($storeId);

        return response()->json($products);
    }
}
