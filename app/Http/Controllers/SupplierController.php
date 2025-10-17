<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Common\FormatApiResponseAction;
use App\Actions\Common\HandleControllerErrorsAction;
use App\Actions\Common\HandleValidatedRequestAction;
use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SupplierController extends Controller
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
            $perPage = $validated['per_page'] ?? 99999;

            $suppliers = Supplier::query()->withCount('products')->paginate($perPage);

            return $this->responseFormatter->paginated($suppliers);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'supplier listing');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Supplier $supplier): void
    {
        dd($supplier);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request);
            $supplier = Supplier::query()->create($validated);

            return $this->responseFormatter->created($supplier, 'Supplier created successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'supplier creation');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier): JsonResponse
    {
        return $this->responseFormatter->resource($supplier->load('products'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request);
            $supplier->update($validated);

            return $this->responseFormatter->updated($supplier->fresh(), 'Supplier updated successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'supplier update');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier): JsonResponse
    {
        try {
            $supplier->delete();

            return $this->responseFormatter->deleted('Supplier deleted successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'supplier deletion');
        }
    }
}
