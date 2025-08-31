<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Common\FormatApiResponseAction;
use App\Actions\Common\HandleControllerErrorsAction;
use App\Actions\Common\HandleValidatedRequestAction;
use App\Http\Requests\StoreSettingRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SettingController extends Controller
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

            $settings = Setting::query()->paginate($perPage);

            return $this->responseFormatter->paginated($settings);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'settings listing');
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSettingRequest $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request);
            $setting = Setting::query()->create($validated);

            return $this->responseFormatter->created($setting, 'Setting created successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'setting creation');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting): JsonResponse
    {
        return $this->responseFormatter->resource($setting);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Setting $setting): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSettingRequest $request, Setting $setting): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request);
            $setting->update($validated);

            return $this->responseFormatter->updated($setting->fresh(), 'Setting updated successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'setting update');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting): JsonResponse
    {
        try {
            $setting->delete();

            return $this->responseFormatter->deleted('Setting deleted successfully');
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'setting deletion');
        }
    }

    /**
     * Save store settings
     */
    public function saveStoreSettings(Request $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request, [
                'name' => 'required|string|max:255',
                'address' => 'required|string',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|max:255',
            ]);

            $setting = Setting::query()->updateOrCreate(
                ['key' => 'store_info'],
                ['value' => $validated]
            );

            return $this->responseFormatter->success('Store settings saved successfully', $setting);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'store settings save');
        }
    }

    /**
     * Save tax settings
     */
    public function saveTaxSettings(Request $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request, [
                'rate' => 'required|numeric|min:0|max:100',
                'name' => 'required|string|max:255',
                'inclusive' => 'boolean',
            ]);

            $setting = Setting::query()->updateOrCreate(
                ['key' => 'tax_settings'],
                ['value' => $validated]
            );

            return $this->responseFormatter->success('Tax settings saved successfully', $setting);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'tax settings save');
        }
    }

    /**
     * Save receipt settings
     */
    public function saveReceiptSettings(Request $request): JsonResponse
    {
        try {
            $validated = $this->validationHandler->execute($request, [
                'header' => 'required|string',
                'footer' => 'required|string',
                'showLogo' => 'boolean',
            ]);

            $setting = Setting::query()->updateOrCreate(
                ['key' => 'receipt_settings'],
                ['value' => $validated]
            );

            return $this->responseFormatter->success('Receipt settings saved successfully', $setting);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'receipt settings save');
        }
    }

    /**
     * Get all settings grouped by category
     */
    public function getAllSettings(): JsonResponse
    {
        try {
            $settings = Setting::all()->pluck('value', 'key');

            return $this->responseFormatter->collection([
                'store_info' => $settings['store_info'] ?? [
                    'name' => 'SuperMarket POS',
                    'address' => '123 Main Street\nAnytown, ST 12345',
                    'phone' => '+1-555-0123',
                    'email' => 'info@supermarketpos.com',
                ],
                'tax_settings' => $settings['tax_settings'] ?? [
                    'rate' => 8.0,
                    'name' => 'Sales Tax',
                    'inclusive' => false,
                ],
                'receipt_settings' => $settings['receipt_settings'] ?? [
                    'header' => 'Thank you for shopping with us!',
                    'footer' => 'Please come again!\nReturn policy: 30 days with receipt',
                    'showLogo' => true,
                ],
            ]);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'settings retrieval');
        }
    }

    /**
     * Get receipt-specific settings (store info + receipt settings)
     */
    public function getReceiptSettings(): JsonResponse
    {
        try {
            $settings = Setting::whereIn('key', ['store_info', 'receipt_settings'])->get()->pluck('value', 'key');

            return $this->responseFormatter->collection([
                'store' => $settings['store_info'] ?? [
                    'name' => 'SuperMarket POS',
                    'address' => '123 Main Street\nAnytown, ST 12345',
                    'phone' => '+1-555-0123',
                    'email' => 'info@supermarketpos.com',
                ],
                'receipt' => $settings['receipt_settings'] ?? [
                    'header' => 'Thank you for shopping with us!',
                    'footer' => 'Please come again!\nReturn policy: 30 days with receipt',
                    'showLogo' => true,
                ],
            ]);
        } catch (Exception $e) {
            return $this->errorHandler->execute($e, 'receipt settings retrieval');
        }
    }
}
