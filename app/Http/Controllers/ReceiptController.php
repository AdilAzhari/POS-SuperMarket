<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ReceiptController extends Controller
{
    public function __construct(
        private readonly ReceiptService $receiptService
    ) {}

    /**
     * Generate receipt for a sale
     */
    public function generate(Sale $sale, Request $request): JsonResponse
    {
        $options = [
            'format' => $request->get('format', 'thermal'),
            'width' => (int) $request->get('width', 58),
        ];

        $receipt = $this->receiptService->generateReceipt($sale, $options);

        return response()->json([
            'success' => true,
            'receipt' => $receipt,
            'sale_id' => $sale->id,
        ]);
    }

    /**
     * Display HTML receipt
     */
    public function show(Sale $sale, Request $request): Response
    {
        $options = [
            'format' => 'html',
            'width' => (int) $request->get('width', 58),
        ];

        $receipt = $this->receiptService->generateReceipt($sale, $options);

        return response($receipt['html'])->header('Content-Type', 'text/html');
    }

    /**
     * Print receipt to thermal printer
     */
    public function print(Sale $sale, Request $request): JsonResponse
    {
        $options = [
            'format' => $request->get('format', 'thermal'),
            'width' => (int) $request->get('width', 58),
        ];

        $success = $this->receiptService->printReceipt($sale, $options);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Receipt printed successfully' : 'Failed to print receipt',
            'sale_id' => $sale->id,
        ]);
    }

    /**
     * Get thermal receipt content
     */
    public function thermal(Sale $sale, Request $request): JsonResponse
    {
        $options = [
            'format' => 'thermal',
            'width' => (int) $request->get('width', 58),
        ];

        $receipt = $this->receiptService->generateReceipt($sale, $options);

        return response()->json([
            'success' => true,
            'content' => $receipt['thermal'],
            'sale_id' => $sale->id,
        ]);
    }

    /**
     * Download PDF receipt (placeholder)
     */
    public function pdf(Sale $sale): Response
    {
        // For now, redirect to HTML version
        // In production, implement actual PDF generation
        return redirect()->route('receipts.show', $sale->id);
    }

    /**
     * Reprint last receipt for a store/cashier
     */
    public function reprint(Request $request): JsonResponse
    {
        $storeId = $request->get('store_id');
        $cashierId = $request->get('cashier_id');

        $lastSale = Sale::where('store_id', $storeId)
            ->where('cashier_id', $cashierId)
            ->latest()
            ->first();

        if (! $lastSale) {
            return response()->json([
                'success' => false,
                'message' => 'No recent sales found',
            ], 404);
        }

        $success = $this->receiptService->printReceipt($lastSale);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Receipt reprinted successfully' : 'Failed to reprint receipt',
            'sale_id' => $lastSale->id,
        ]);
    }

    /**
     * Get receipt settings
     */
    public function settings(): JsonResponse
    {
        $settings = \App\Models\Setting::where('category', 'receipt')
            ->orWhere('category', 'printer')
            ->get()
            ->groupBy('category')
            ->map(fn ($items) => $items->pluck('value', 'key'));

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Update receipt settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receipt.header' => 'nullable|string|max:255',
            'receipt.footer' => 'nullable|string|max:255',
            'receipt.show_logo' => 'boolean',
            'receipt.show_customer_info' => 'boolean',
            'receipt.show_loyalty_points' => 'boolean',
            'printer.enabled' => 'boolean',
            'printer.connection_type' => 'required|in:usb,network,bluetooth',
            'printer.usb_device' => 'nullable|string',
            'printer.network_ip' => 'nullable|ip',
            'printer.network_port' => 'nullable|integer|min:1|max:65535',
            'printer.paper_width' => 'integer|in:58,80',
            'printer.auto_print' => 'boolean',
        ]);

        foreach ($validated as $category => $settings) {
            foreach ($settings as $key => $value) {
                \App\Models\Setting::updateOrCreate(
                    ['category' => $category, 'key' => $key],
                    ['value' => is_bool($value) ? ($value ? 'true' : 'false') : $value]
                );
            }
        }

        // Clear cache
        \Illuminate\Support\Facades\Cache::forget('receipts:settings');
        \Illuminate\Support\Facades\Cache::forget('receipts:printer_settings');

        return response()->json([
            'success' => true,
            'message' => 'Receipt settings updated successfully',
        ]);
    }
}
