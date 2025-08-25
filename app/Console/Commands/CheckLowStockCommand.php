<?php

namespace App\Console\Commands;

use App\Services\InventoryAlertService;
use Illuminate\Console\Command;

class CheckLowStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'inventory:check-low-stock 
                            {--send-alerts : Send alerts to managers}
                            {--store= : Check specific store only}
                            {--update-thresholds : Update optimal thresholds based on sales patterns}';

    /**
     * The console command description.
     */
    protected $description = 'Check for low stock products and optionally send alerts';

    public function __construct(
        private readonly InventoryAlertService $inventoryAlertService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking inventory levels...');

        $storeId = $this->option('store');

        if ($storeId) {
            $this->checkSpecificStore((int) $storeId);
        } else {
            $this->checkAllStores();
        }

        if ($this->option('send-alerts')) {
            $this->info('📧 Sending low stock alerts...');
            $alertsSent = $this->inventoryAlertService->sendLowStockAlerts();
            $this->info("✅ Alerts sent to {$alertsSent} managers");
        }

        if ($this->option('update-thresholds')) {
            $this->info('🔧 Updating optimal stock thresholds...');
            $this->updateThresholds($storeId);
        }

        return Command::SUCCESS;
    }

    /**
     * Check specific store
     */
    private function checkSpecificStore(int $storeId): void
    {
        $lowStock = $this->inventoryAlertService->getLowStockForStore($storeId);

        $this->info("📊 Store {$storeId} Inventory Report:");
        $this->info("   Low stock products: {$lowStock->count()}");

        $critical = $lowStock->where('severity', '>=', 4);
        if ($critical->isNotEmpty()) {
            $this->warn("   ⚠️  Critical/Out of stock: {$critical->count()}");

            $this->table(
                ['Product', 'SKU', 'Current Stock', 'Threshold', 'Severity'],
                $critical->take(10)->map(fn ($item) => [
                    $item['product']->name,
                    $item['product']->sku,
                    $item['current_stock'],
                    $item['threshold'],
                    $this->getSeverityText($item['severity']),
                ])
            );
        }

        // Show reorder suggestions
        $suggestions = $this->inventoryAlertService->generateReorderSuggestions($storeId);
        if ($suggestions->isNotEmpty()) {
            $this->info("\n💡 Top Reorder Suggestions:");
            $this->table(
                ['Product', 'Current Stock', 'Suggested Qty', 'Est. Cost'],
                $suggestions->take(5)->map(fn ($item) => [
                    $item['product']->name,
                    $item['current_stock'],
                    $item['suggested_quantity'],
                    '$'.number_format($item['estimated_cost'], 2),
                ])
            );
        }
    }

    /**
     * Check all stores
     */
    private function checkAllStores(): void
    {
        $lowStockData = $this->inventoryAlertService->getLowStockProducts();
        $criticalStock = $this->inventoryAlertService->getCriticalLowStock();

        $this->info('🏪 All Stores Inventory Summary:');
        $this->info('   Stores with low stock: '.count($lowStockData));
        $this->info('   Total low stock products: '.collect($lowStockData)->sum(fn ($store) => $store['products']->count()));
        $this->warn("   Critical/Out of stock items: {$criticalStock->count()}");

        if (! empty($lowStockData)) {
            $this->table(
                ['Store', 'Low Stock Products', 'Critical Items'],
                collect($lowStockData)->map(fn ($storeData) => [
                    $storeData['store']->name,
                    $storeData['products']->count(),
                    $storeData['products']->where('severity', '>=', 4)->count(),
                ])
            );
        }

        // Show most critical items across all stores
        if ($criticalStock->isNotEmpty()) {
            $this->warn("\n⚠️  Most Critical Items:");
            $this->table(
                ['Store', 'Product', 'Current Stock', 'Status'],
                $criticalStock->take(10)->map(fn ($item) => [
                    $item['store']->name,
                    $item['product']->name,
                    $item['current_stock'],
                    $item['is_out_of_stock'] ? 'OUT OF STOCK' : 'VERY LOW',
                ])
            );
        }
    }

    /**
     * Update thresholds for stores
     */
    private function updateThresholds(?string $storeId): void
    {
        if ($storeId) {
            $updated = $this->inventoryAlertService->updateOptimalThresholds((int) $storeId);
            $this->info("   ✅ Updated {$updated} thresholds for store {$storeId}");
        } else {
            $stores = \App\Models\Store::all();
            $totalUpdated = 0;

            foreach ($stores as $store) {
                $updated = $this->inventoryAlertService->updateOptimalThresholds($store->id);
                $totalUpdated += $updated;

                if ($updated > 0) {
                    $this->info("   ✅ {$store->name}: Updated {$updated} thresholds");
                }
            }

            $this->info("   📊 Total thresholds updated: {$totalUpdated}");
        }
    }

    /**
     * Get severity text representation
     */
    private function getSeverityText(int $severity): string
    {
        return match ($severity) {
            5 => '🔴 OUT OF STOCK',
            4 => '🟠 CRITICAL',
            3 => '🟡 LOW',
            2 => '🟢 WARNING',
            1 => '🔵 NOTICE',
            default => 'OK'
        };
    }
}
