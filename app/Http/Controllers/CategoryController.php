<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Common\FormatApiResponseAction;
use App\Actions\Common\HandleControllerErrorsAction;
use App\Actions\Common\HandleValidatedRequestAction;
use App\Actions\Models\GenerateCategorySlugAction;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CategoryController extends Controller
{
    public function __construct(
        private readonly HandleValidatedRequestAction $validationHandler,
        private readonly FormatApiResponseAction $responseFormatter,
        private readonly HandleControllerErrorsAction $errorHandler
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->validatePagination($request);
            $perPage = $validated['per_page'] ?? 9999;

            $categories = Category::query()->withCount('products')->paginate($perPage);

            return $this->responseFormatter->paginated($categories);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'category listing');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request, GenerateCategorySlugAction $generateCategorySlugAction): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request);
            $category = Category::query()->create($validated);

            return $this->responseFormatter->created($category, 'Category created successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'category creation');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): JsonResponse
    {
        return $this->responseFormatter->resource($category->load('products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreCategoryRequest $request, Category $category): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request);
            $category->update($validated);

            return $this->responseFormatter->updated($category->fresh(), 'Category updated successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'category update');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            $category->delete();

            return $this->responseFormatter->deleted('Category deleted successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'category deletion');
        }
    }
}
