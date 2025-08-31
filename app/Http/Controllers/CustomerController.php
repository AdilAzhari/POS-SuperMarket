<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Common\FormatApiResponseAction;
use App\Actions\Common\HandleControllerErrorsAction;
use App\Actions\Common\HandleValidatedRequestAction;
use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerController extends Controller
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
            $perPage = $validated['per_page'] ?? 20;

            $customers = Customer::query()->orderByDesc('created_at')->paginate($perPage);

            return $this->responseFormatter->paginated($customers);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'customer listing');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request);
            $customer = Customer::query()->create($validated);

            return $this->responseFormatter->created($customer, 'Customer created successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'customer creation');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer): JsonResponse
    {
        return $this->responseFormatter->resource($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreCustomerRequest $request, Customer $customer): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request);
            $customer->update($validated);

            return $this->responseFormatter->updated($customer->fresh(), 'Customer updated successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'customer update');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $customer->delete();

            return $this->responseFormatter->deleted('Customer deleted successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'customer deletion');
        }
    }
}
