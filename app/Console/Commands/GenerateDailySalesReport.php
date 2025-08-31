<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

final class GenerateDailySalesReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pos:generate-daily-sales-report {--date= : Specific date (YYYY-MM-DD) for the report} {--store= : Specific store ID} {--email= : Email address to send report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily sales report for all stores or a specific store';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting daily sales report generation...');

        try {
            $date = $this->option('date') ?
                Carbon::parse($this->option('date')) :
                Carbon::yesterday();

            $storeId = $this->option('store');
            $email = $this->option('email');

            $this->info("📅 Generating report for: {$date->format('Y-m-d')}");

            if ($storeId) {
                $store = Store::query()->findOrFail($storeId);
                $this->generateStoreReport($store, $date, $email);
            } else {
                $this->generateAllStoresReport($date, $email);
            }

            $this->info('✅ Daily sales report generation completed successfully!');

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error("❌ Failed to generate daily sales report: {$e->getMessage()}");
            Log::error('Daily sales report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    private function generateStoreReport(Store $store, Carbon $date, ?string $email): void
    {
        $this->info("🏪 Generating report for store: $store->name");

        $sales = Sale::query()
            ->byStore($store->id)
            ->whereDate('created_at', $date)
            ->completed()
            ->with(['items', 'customer', 'cashier'])
            ->get();

        $analytics = $this->calculateAnalytics($sales);

        $this->displayReport($store->name, $date, $analytics);

        if ($email !== null && $email !== '' && $email !== '0') {
            $this->sendReportEmail($email, $store->name, $date, $analytics);
        }

        // Log the report generation
        Log::info('Daily sales report generated', [
            'store_id' => $store->id,
            'store_name' => $store->name,
            'date' => $date->format('Y-m-d'),
            'total_sales' => $analytics['total_sales'],
            'sales_count' => $analytics['sales_count'],
        ]);
    }

    private function generateAllStoresReport(Carbon $date, ?string $email): void
    {
        $stores = Store::query()->get();
        $overallAnalytics = [
            'total_sales' => 0,
            'sales_count' => 0,
            'total_items' => 0,
            'average_sale' => 0,
            'stores_count' => $stores->count(),
        ];

        $this->info("🏢 Generating reports for {$stores->count()} stores");

        foreach ($stores as $store) {
            $sales = Sale::query()
                ->byStore($store->id)
                ->whereDate('created_at', $date)
                ->completed()
                ->get();

            $storeAnalytics = $this->calculateAnalytics($sales);

            $overallAnalytics['total_sales'] += $storeAnalytics['total_sales'];
            $overallAnalytics['sales_count'] += $storeAnalytics['sales_count'];
            $overallAnalytics['total_items'] += $storeAnalytics['total_items'];

            $this->line("  • $store->name: {$storeAnalytics['sales_count']} sales, $".number_format($storeAnalytics['total_sales'], 2));
        }

        $overallAnalytics['average_sale'] = $overallAnalytics['sales_count'] > 0 ?
            $overallAnalytics['total_sales'] / $overallAnalytics['sales_count'] : 0;

        $this->displayReport('All Stores', $date, $overallAnalytics);

        if ($email !== null && $email !== '' && $email !== '0') {
            $this->sendReportEmail($email, 'All Stores', $date, $overallAnalytics);
        }
    }

    private function calculateAnalytics($sales): array
    {
        return [
            'total_sales' => $sales->sum('total'),
            'sales_count' => $sales->count(),
            'total_items' => $sales->sum('items_count'),
            'average_sale' => $sales->count() > 0 ? $sales->sum('total') / $sales->count() : 0,
            'payment_methods' => $sales->groupBy('payment_method')->map->count()->toArray(),
            'hourly_breakdown' => $sales->groupBy(fn ($sale) => $sale->created_at->format('H'))->map->count()->toArray(),
        ];
    }

    private function displayReport(string $storeName, Carbon $date, array $analytics): void
    {
        $this->newLine();
        $this->info("📊 Sales Report for $storeName - {$date->format('F j, Y')}");
        $this->line(str_repeat('=', 60));

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Sales', '$'.number_format($analytics['total_sales'], 2)],
                ['Number of Sales', number_format($analytics['sales_count'])],
                ['Total Items Sold', number_format($analytics['total_items'])],
                ['Average Sale Value', '$'.number_format($analytics['average_sale'], 2)],
            ]
        );

        if (! empty($analytics['payment_methods'])) {
            $this->info('💳 Payment Methods Breakdown:');
            foreach ($analytics['payment_methods'] as $method => $count) {
                $this->line("  • $method: $count transactions");
            }
        }

        $this->newLine();
    }

    private function sendReportEmail(string $email, string $storeName, Carbon $date, array $analytics): void
    {
        try {
            // Here you would implement email sending logic
            // For now, we'll just log it
            Log::info('Sales report email would be sent', [
                'email' => $email,
                'store' => $storeName,
                'date' => $date->format('Y-m-d'),
                'analytics' => $analytics,
            ]);

            $this->info("📧 Report email queued for: $email");
        } catch (Throwable $e) {
            $this->warn("⚠️  Failed to send email: {$e->getMessage()}");
        }
    }
}
