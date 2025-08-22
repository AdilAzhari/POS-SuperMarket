<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSettingRequest;
use App\Http\Requests\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(Setting::query()->paginate(20));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSettingRequest $request): JsonResponse
    {
        $setting = Setting::query()->create($request->validated());

        return response()->json($setting, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting): JsonResponse
    {
        return response()->json($setting);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Setting $setting)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSettingRequest $request, Setting $setting): JsonResponse
    {
        $setting->update($request->validated());

        return response()->json($setting->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting): Response
    {
        $setting->delete();

        return response()->noContent();
    }

    /**
     * Save store settings
     */
    public function saveStoreSettings(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);

        $setting = Setting::query()->updateOrCreate(
            ['key' => 'store_info'],
            ['value' => $request->all()]
        );

        return response()->json(['message' => 'Store settings saved successfully', 'data' => $setting]);
    }

    /**
     * Save tax settings
     */
    public function saveTaxSettings(Request $request): JsonResponse
    {
        $request->validate([
            'rate' => 'required|numeric|min:0|max:100',
            'name' => 'required|string|max:255',
            'inclusive' => 'boolean',
        ]);

        $setting = Setting::query()->updateOrCreate(
            ['key' => 'tax_settings'],
            ['value' => $request->all()]
        );

        return response()->json(['message' => 'Tax settings saved successfully', 'data' => $setting]);
    }

    /**
     * Save receipt settings
     */
    public function saveReceiptSettings(Request $request): JsonResponse
    {
        $request->validate([
            'header' => 'required|string',
            'footer' => 'required|string',
            'showLogo' => 'boolean',
        ]);

        $setting = Setting::query()->updateOrCreate(
            ['key' => 'receipt_settings'],
            ['value' => $request->all()]
        );

        return response()->json(['message' => 'Receipt settings saved successfully', 'data' => $setting]);
    }

    /**
     * Get all settings grouped by category
     */
    public function getAllSettings(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');

        return response()->json([
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
    }
}
