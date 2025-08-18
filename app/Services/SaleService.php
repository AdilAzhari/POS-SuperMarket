<?php

namespace App\Services;

use App\DTOs\CreateSaleDTO;
use App\DTOs\SaleResponseDTO;
use App\DTOs\SaleItemDTO;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\SaleProcessingException;

class SaleService extends BaseService
{
    protected string $cachePrefix = 'sales:';

    protected function logInfo(string $message, array $context = []): void
    {

        Log::channel('sales')->info("[{$this->getServiceName()}] {$message}", $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::channel('sales')->error("[{$this->getServiceName()}] {$message}", $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        Log::channel('sales')->warning("[{$this->getServiceName()}] {$message}", $context);
    }

    public function __construct(
        private StockService $stockService
    ) {
    }

    public function getPaginatedSales(int $perPage = 20): LengthAwarePaginator
    {
        $this->logInfo('Fetching paginated sales', ['per_page' => $perPage]);

        return $this->remember(
            "paginated:{$perPage}",
            fn() => Sale::with([
                    'store:id,name', 
                    'customer:id,name,email', 
                    'cashier:id,name',
                    'items:id,sale_id,product_name,quantity,price,line_total'
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
            "sale:{$id}",
            fn() => Sale::with(['items', 'store', 'customer', 'cashier'])->findOrFail($id)
        );

        return SaleResponseDTO::fromModel($sale);
    }

    public function createSale(CreateSaleDTO $saleData): SaleResponseDTO
    {
        $this->logInfo('Creating new sale', [
            'store_id' => $saleData->store_id,
            'cashier_id' => $saleData->cashier_id,
            'customer_id' => $saleData->customer_id,
            'items_count' => count($saleData->items)
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
                $sale = Sale::create([
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

                // Update customer statistics
                $this->updateCustomerStats($saleData->customer_id, $totals['total']);

                return $sale->load(['items', 'store', 'customer', 'cashier']);
            });

            $this->clearSalesCaches();

            $this->logInfo('Sale created successfully', [
                'sale_id' => $sale->id,
                'sale_code' => $sale->code,
                'total' => $sale->total
            ]);

            return SaleResponseDTO::fromModel($sale);

        } catch (InsufficientStockException $e) {
            // Re-throw stock exceptions without wrapping
            throw $e;
        } catch (\Throwable $e) {
            $this->logError('Failed to create sale', [
                'error' => $e->getMessage(),
                'store_id' => $saleData->store_id,
                'cashier_id' => $saleData->cashier_id
            ]);

            throw new SaleProcessingException('Failed to process sale: ' . $e->getMessage(), 0, $e);
        }
    }

    private function generateTransactionCode(): string
    {
        $nextId = Sale::max('id') + 1;
        return 'TXN-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }

    private function validateStockAvailability(CreateSaleDTO $saleData): void
    {
        foreach ($saleData->getSaleItems() as $item) {
            $product = Product::findOrFail($item->product_id);
            $availableStock = $this->stockService->getStockForStore($product->id, $saleData->store_id);

            if ($availableStock < $item->quantity) {
                throw new InsufficientStockException(
                    "Insufficient stock for product {$product->name}. Available: {$availableStock}, Required: {$item->quantity}"
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
            'total' => $total
        ];
    }

    private function createSaleItems(Sale $sale, CreateSaleDTO $saleData): void
    {
        foreach ($saleData->getSaleItems() as $item) {
            $product = Product::findOrFail($item->product_id);

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

    private function updateCustomerStats(?int $customerId, float $total): void
    {
        if (!$customerId) {
            return;
        }

        $customer = Customer::find($customerId);
        if ($customer) {
            $customer->increment('total_purchases');
            $customer->update([
                'total_spent' => ($customer->total_spent ?? 0) + $total,
                'last_purchase_at' => now(),
            ]);

            $this->logInfo('Updated customer statistics', [
                'customer_id' => $customerId,
                'purchase_amount' => $total
            ]);
        }
    }

    public function getSalesAnalytics(int $storeId, int $days = 30): array
    {
        $this->logInfo('Fetching sales analytics', ['store_id' => $storeId, 'days' => $days]);

        return $this->remember(
            "analytics:store:{$storeId}:days:{$days}",
            fn() => [
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
            "today:store:{$storeId}:" . today()->format('Y-m-d'),
            fn() => [
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
            "payment_methods:store:{$storeId}:days:{$days}",
            fn() => Sale::byStore($storeId)
                ->completed()
                ->where('created_at', '>=', now()->subDays($days))
                ->selectRaw('payment_method, SUM(total) as total, COUNT(*) as count')
                ->groupBy('payment_method')
                ->orderByDesc('total')
                ->get(),
            1800 // 30 minutes cache
        );
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
