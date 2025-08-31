<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\CreateSaleDTO;
use App\DTOs\SaleResponseDTO;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\SaleProcessingException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SaleService extends BaseService
{
    protected string $cachePrefix = 'sales:';

    public function __construct(
        private readonly StockService $stockService,
        private readonly LoyaltyService $loyaltyService,
        private readonly ReceiptService $receiptService
    ) {}

    public function getPaginatedSales(int $perPage = 20): LengthAwarePaginator
    {
        $this->logInfo('Fetching paginated sales', ['per_page' => $perPage]);

        return $this->remember(
            "paginated:$perPage",
            fn () => Sale::with([
                'store:id,name',
                'customer:id,name,email',
                'cashier:id,name',
                'items:id,sale_id,product_name,sku,quantity,price,discount,tax,line_total',
            ])
                ->completed()
                ->orderByDesc('created_at')
                ->paginate($perPage),
            300 // 5 minutes cache
        );
    }

    public function getSaleById(int $id): SaleResponseDTO
    {
        $this->logInfo('Fetching sale by ID', ['sale_id' => $id]);

        $sale = $this->remember(
            "sale:$id",
            fn () => Sale::with(['items', 'store', 'customer', 'cashier', 'latestPayment'])->findOrFail($id)
        );

        return SaleResponseDTO::fromModel($sale);
    }

    /**
     * @throws SaleProcessingException
     */
    public function createSale(CreateSaleDTO $saleData): SaleResponseDTO
    {
        $this->logInfo('Creating new sale', [
            'store_id' => $saleData->store_id,
            'cashier_id' => $saleData->cashier_id,
            'customer_id' => $saleData->customer_id,
            'items_count' => count($saleData->items),
        ]);

        try {
            $sale = DB::transaction(function () use ($saleData) {
                // Generate transaction code
                $code = $this->generateTransactionCode();

                // Validate stock availability
                $this->validateStockAvailability($saleData);

                // Calculate totals
                $totals = $this->calculateSaleTotals($saleData);

                // Create sale record
                $sale = Sale::query()->create([
                    'code' => $code,
                    'store_id' => $saleData->store_id,
                    'customer_id' => $saleData->customer_id,
                    'cashier_id' => $saleData->cashier_id,
                    'items_count' => $totals['items_count'],
                    'subtotal' => $totals['subtotal'],
                    'discount' => $saleData->discount,
                    'tax' => $saleData->tax,
                    'total' => $totals['total'],
                    'payment_method' => $saleData->payment_method,
                    'status' => 'completed',
                    'paid_at' => $saleData->paid_at ?? now(),
                ]);

                // Create sale items and update stock
                $this->createSaleItems($sale, $saleData);

                Log::info('Sale items created', ['sale_id' => $sale->id, 'items_count' => $sale->items_count]);
                // Update customer statistics and process loyalty
                $loyaltyResult = $this->updateCustomerStatsAndLoyalty($sale);

                return $sale->load(['items', 'store', 'customer', 'cashier']);
            });

            $this->clearSalesCaches();

            $this->logInfo('Sale created successfully', [
                'sale_id' => $sale->id,
                'sale_code' => $sale->code,
                'total' => $sale->total,
            ]);

            // Save receipt for future reference
            $this->receiptService->saveReceipt($sale);

            // Auto-print receipt if enabled
            $printerSettings = Setting::query()
                ->where('key', 'printer_auto_print')
                ->value('value');

            if ($printerSettings === 'true') {
                $this->receiptService->printReceipt($sale);
            }

            return SaleResponseDTO::fromModel($sale);

        } catch (InsufficientStockException $e) {
            $this->logWarning('Insufficient stock for sale', [
                'error' => $e->getMessage(),
                'store_id' => $saleData->store_id,
                'cashier_id' => $saleData->cashier_id,
            ]);

            throw $e; // Re-throw without wrapping
        } catch (Throwable $e) {
            $this->logError('Failed to create sale', [
                'error' => $e->getMessage(),
                'store_id' => $saleData->store_id,
                'cashier_id' => $saleData->cashier_id,
            ]);

            throw new SaleProcessingException('Failed to process sale: '.$e->getMessage(), 0, $e);
        }
    }

    public function getSalesAnalytics(int $storeId, int $days = 30): array
    {
        $this->logInfo('Fetching sales analytics', ['store_id' => $storeId, 'days' => $days]);

        return $this->remember(
            "analytics:store:{$storeId}:days:$days",
            fn (): array => [
                'total_sales' => Sale::byStore($storeId)
                    ->completed()
                    ->where('created_at', '>=', now()->subDays($days))
                    ->sum('total'),
                'sales_count' => Sale::byStore($storeId)
                    ->completed()
                    ->where('created_at', '>=', now()->subDays($days))
                    ->count(),
                'avg_sale_value' => Sale::byStore($storeId)
                    ->completed()
                    ->where('created_at', '>=', now()->subDays($days))
                    ->avg('total'),
                'daily_sales' => Sale::byStore($storeId)
                    ->completed()
                    ->where('created_at', '>=', now()->subDays($days))
                    ->selectRaw('DATE(created_at) as date, SUM(total) as total, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->keyBy('date'),
            ],
            1800 // 30 minutes cache
        );
    }

    public function getTodaySales(int $storeId): array
    {
        $this->logInfo('Fetching today sales', ['store_id' => $storeId]);

        return $this->remember(
            "today:store:$storeId:".today()->format('Y-m-d'),
            fn (): array => [
                'total' => Sale::byStore($storeId)->today()->completed()->sum('total'),
                'count' => Sale::byStore($storeId)->today()->completed()->count(),
                'items_sold' => Sale::byStore($storeId)->today()->completed()->sum('items_count'),
            ],
            300 // 5 minutes cache
        );
    }

    public function getPaymentMethodBreakdown(int $storeId, int $days = 30): Collection
    {
        $this->logInfo('Fetching payment method breakdown', ['store_id' => $storeId, 'days' => $days]);

        return $this->remember(
            "payment_methods:store:$storeId:days:$days",
            fn () => Sale::byStore($storeId)
                ->completed()
                ->where('created_at', '>=', now()->subDays($days))
                ->selectRaw('payment_method, SUM(total) as total, COUNT(*) as count')
                ->groupBy('payment_method')
                ->orderByDesc('total')
                ->get(),
            1800 // 30 minutes cache
        );
    }

    protected function logInfo(string $message, array $context = []): void
    {

        Log::channel('sales')->info("[{$this->getServiceName()}] $message", $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::channel('sales')->error("[{$this->getServiceName()}] $message", $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        Log::channel('sales')->warning("[{$this->getServiceName()}] $message", $context);
    }

    private function generateTransactionCode(): string
    {
        $attempts = 0;
        $maxAttempts = 10;

        do {
            // Generate a unique code using timestamp, microseconds and random number
            $timestamp = now()->format('ymdHis');
            $microseconds = mb_str_pad((string) (microtime(true) * 1000 % 1000), 3, '0', STR_PAD_LEFT);
            $random = mb_str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT);
            $code = "TXN-{$timestamp}-{$microseconds}-{$random}";

            // Check if code already exists
            $exists = Sale::where('code', $code)->exists();
            $attempts++;

            if (! $exists) {
                return $code;
            }

            // Add small delay to avoid collision
            usleep(1000); // 1ms

        } while ($attempts < $maxAttempts);

        // Fallback: use UUID if all attempts failed
        $uuid = str_replace('-', '', mb_substr((string) \Illuminate\Support\Str::uuid(), 0, 12));

        return "TXN-{$uuid}";
    }

    /**
     * @throws InsufficientStockException
     */
    private function validateStockAvailability(CreateSaleDTO $saleData): void
    {
        foreach ($saleData->getSaleItems() as $item) {
            $product = Product::query()->findOrFail($item->product_id);
            $availableStock = $this->stockService->getStockForStore($product->id, $saleData->store_id);

            if ($availableStock < $item->quantity) {
                throw new InsufficientStockException(
                    "Insufficient stock for product $product->name. Available: $availableStock, Required: $item->quantity"
                );
            }
        }
    }

    private function calculateSaleTotals(CreateSaleDTO $saleData): array
    {
        $itemsCount = 0;
        $subtotal = 0.0;

        foreach ($saleData->getSaleItems() as $item) {
            $itemsCount += $item->quantity;
            $subtotal += ($item->price * $item->quantity);
        }

        $total = $subtotal - $saleData->discount + $saleData->tax;

        return [
            'items_count' => $itemsCount,
            'subtotal' => $subtotal,
            'total' => $total,
        ];
    }

    private function createSaleItems(Sale $sale, CreateSaleDTO $saleData): void
    {
        foreach ($saleData->getSaleItems() as $item) {
            $product = Product::query()->findOrFail($item->product_id);

            $lineTotal = $item->calculateLineTotal();

            // Create sale item
            $sale->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'price' => $item->price,
                'quantity' => $item->quantity,
                'discount' => $item->discount,
                'tax' => $item->tax,
                'line_total' => $lineTotal,
            ]);

            // Update stock
            $this->stockService->decrementStock($product->id, $saleData->store_id, $item->quantity);
        }
    }

    private function updateCustomerStatsAndLoyalty(Sale $sale): array
    {
        if (! $sale->customer_id) {
            return ['points_earned' => 0, 'tier_upgraded' => false];
        }

        // Process loyalty points through LoyaltyService
        $loyaltyResult = $this->loyaltyService->processSaleLoyalty($sale);

        $this->logInfo('Updated customer statistics and loyalty', [
            'customer_id' => $sale->customer_id,
            'purchase_amount' => (float) $sale->total,
            'points_earned' => $loyaltyResult['points_earned'] ?? 0,
            'tier_upgraded' => $loyaltyResult['tier_upgraded'] ?? false,
        ]);

        return $loyaltyResult;
    }

    private function clearSalesCaches(): void
    {
        $this->forget('paginated:20');
        $this->forget('paginated:10');
        $this->forget('paginated:50');

        // Clear analytics caches (basic patterns)
        Cache::forget($this->cacheKey('analytics:*'));
        Cache::forget($this->cacheKey('today:*'));
        Cache::forget($this->cacheKey('payment_methods:*'));
    }
}
