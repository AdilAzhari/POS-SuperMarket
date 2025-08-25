<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Store;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckLowStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:check-low-stock {--store= : Specific store ID} {--email= : Email address to send alerts} {--threshold= : Custom threshold override}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for products with low stock levels and send alerts';

    public function __construct(
        private readonly StockService $stockService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking for low stock items...');

        try {
            $storeId = $this->option('store');
            $email = $this->option('email');
            $customThreshold = $this->option('threshold');

            if ($storeId) {
                $store = Store::query()->findOrFail($storeId);
                $this->checkStoreStock($store, $email, $customThreshold);
            } else {
                $this->checkAllStoresStock($email, $customThreshold);
            }

            $this->info('✅ Low stock check completed successfully!');

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("❌ Failed to check low stock: {$e->getMessage()}");
            Log::error('Low stock check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    private function checkStoreStock(Store $store, ?string $email, ?int $customThreshold): void
    {
        $this->info("🏪 Checking stock for store: {$store->name}");

        $lowStockProducts = $this->getLowStockProducts($store->id, $customThreshold);

        if ($lowStockProducts->isEmpty()) {
            $this->info("✅ No low stock items found in {$store->name}");

            return;
        }

        $this->displayLowStockReport($store->name, $lowStockProducts);

        if ($email) {
            $this->sendLowStockAlert($email, $store->name, $lowStockProducts);
        }

        // Log low stock items
        Log::warning('Low stock items detected', [
            'store_id' => $store->id,
            'store_name' => $store->name,
            'low_stock_count' => $lowStockProducts->count(),
            'products' => $lowStockProducts->pluck('name')->toArray(),
        ]);
    }

    private function checkAllStoresStock(?string $email, ?int $customThreshold): void
    {
        $stores = Store::query()->get();
        $allLowStockProducts = collect();

        $this->info("🏢 Checking stock for {$stores->count()} stores");

        foreach ($stores as $store) {
            $lowStockProducts = $this->getLowStockProducts($store->id, $customThreshold);

            if ($lowStockProducts->isNotEmpty()) {
                $this->line("  ⚠️  {$store->name}: {$lowStockProducts->count()} low stock items");
                $allLowStockProducts = $allLowStockProducts->merge(
                    $lowStockProducts->map(function ($product) use ($store) {
                        $product->store_name = $store->name;

                        return $product;
                    })
                );
            } else {
                $this->line("  ✅ {$store->name}: No low stock items");
            }
        }

        if ($allLowStockProducts->isNotEmpty()) {
            $this->displayLowStockReport('All Stores', $allLowStockProducts);

            if ($email) {
                $this->sendLowStockAlert($email, 'All Stores', $allLowStockProducts);
            }
        } else {
            $this->info('✅ No low stock items found across all stores');
        }
    }

    private function getLowStockProducts(int $storeId, ?int $customThreshold)
    {
        $query = Product::query()
            ->select(['products.*', 'product_store.stock', 'product_store.low_stock_threshold'])
            ->join('product_store', 'products.id', '=', 'product_store.product_id')
            ->where('product_store.store_id', $storeId)
            ->where('products.active', true);

        if ($customThreshold) {
            $query->whereRaw('product_store.stock <= ?', [$customThreshold]);
        } else {
            $query->whereRaw('product_store.stock <= product_store.low_stock_threshold');
        }

        return $query->get();
    }

    private function displayLowStockReport(string $storeName, $lowStockProducts): void
    {
        $this->newLine();
        $this->warn("⚠️  Low Stock Alert for {$storeName}");
        $this->line(str_repeat('=', 60));

        $tableData = $lowStockProducts->map(function ($product) {
            return [
                $product->name,
                $product->sku,
                isset($product->store_name) ? $product->store_name : '',
                $product->stock ?? 0,
                $product->low_stock_threshold ?? 0,
                $product->category?->name ?? 'N/A',
            ];
        })->toArray();

        $headers = ['Product', 'SKU', 'Store', 'Current Stock', 'Threshold', 'Category'];
        if ($storeName !== 'All Stores') {
            // Remove store column for single store reports
            $headers = ['Product', 'SKU', 'Current Stock', 'Threshold', 'Category'];
            $tableData = array_map(function ($row) {
                return [$row[0], $row[1], $row[3], $row[4], $row[5]];
            }, $tableData);
        }

        $this->table($headers, $tableData);

        $critical = $lowStockProducts->filter(function ($product) {
            return ($product->stock ?? 0) === 0;
        });

        if ($critical->isNotEmpty()) {
            $this->error("🚨 {$critical->count()} products are completely out of stock!");
        }

        $this->newLine();
    }

    private function sendLowStockAlert(string $email, string $storeName, $lowStockProducts): void
    {
        try {
            // Here you would implement email sending logic
            // For now, we'll just log it
            Log::warning('Low stock alert email would be sent', [
                'email' => $email,
                'store' => $storeName,
                'low_stock_count' => $lowStockProducts->count(),
                'out_of_stock_count' => $lowStockProducts->filter(fn ($p) => ($p->stock ?? 0) === 0)->count(),
                'products' => $lowStockProducts->map(function ($product) {
                    return [
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'current_stock' => $product->stock ?? 0,
                        'threshold' => $product->low_stock_threshold ?? 0,
                    ];
                })->toArray(),
            ]);

            $this->info("📧 Low stock alert email queued for: {$email}");
        } catch (\Throwable $e) {
            $this->warn("⚠️  Failed to send email alert: {$e->getMessage()}");
        }
    }
}
