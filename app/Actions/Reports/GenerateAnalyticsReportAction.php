<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class GenerateAnalyticsReportAction
{
    /**
     * Generate comprehensive analytics report
     */
    public function execute(string $dateRange, ?int $storeId, User $user): array
    {
        $dates = $this->getDateRange($dateRange);

        return [
            'overview' => $this->getOverviewMetrics($dates, $storeId, $user),
            'sales_trend' => $this->getSalesTrend($dates, $storeId, $user),
            'top_products' => $this->getTopProducts($dates, $storeId, $user),
            'employee_performance' => $this->getEmployeePerformance($dates, $storeId, $user),
            'customer_insights' => $this->getCustomerInsights($dates, $storeId, $user),
            'store_comparison' => $this->getStoreComparison($dates, $user),
            'inventory_analysis' => $this->getInventoryAnalysis($dates, $storeId, $user),
        ];
    }

    private function getDateRange(string $dateRange): array
    {
        return match ($dateRange) {
            'today' => [Carbon::today(), Carbon::today()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'quarter' => [Carbon::now()->startOfQuarter(), Carbon::now()->endOfQuarter()],
            'year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }

    private function getOverviewMetrics(array $dates, ?int $storeId, User $user): array
    {
        [$startDate, $endDate] = $dates;

        $baseQuery = DB::table('sales')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($storeId && $user->canAccessStore($storeId)) {
            $baseQuery->where('store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $baseQuery->whereIn('store_id', $user->accessibleStores()->pluck('id'));
        }

        return [
            'total_sales' => $baseQuery->sum('total'),
            'total_transactions' => $baseQuery->count(),
            'average_transaction' => $baseQuery->avg('total'),
            'total_items_sold' => $baseQuery->sum('items_count'),
        ];
    }

    private function getSalesTrend(array $dates, ?int $storeId, User $user): array
    {
        [$startDate, $endDate] = $dates;

        $query = DB::table('sales')
            ->selectRaw('DATE(created_at) as date, SUM(total) as daily_total, COUNT(*) as transaction_count')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date');

        if ($storeId && $user->canAccessStore($storeId)) {
            $query->where('store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $query->whereIn('store_id', $user->accessibleStores()->pluck('id'));
        }

        return $query->get()->toArray();
    }

    private function getTopProducts(array $dates, ?int $storeId, User $user): array
    {
        [$startDate, $endDate] = $dates;

        $query = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->selectRaw('
                products.id,
                products.name,
                products.sku,
                SUM(sale_items.quantity) as total_quantity,
                SUM(sale_items.quantity * sale_items.price) as total_revenue,
                COUNT(DISTINCT sales.id) as order_count
            ')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_revenue')
            ->limit(10);

        if ($storeId && $user->canAccessStore($storeId)) {
            $query->where('sales.store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $query->whereIn('sales.store_id', $user->accessibleStores()->pluck('id'));
        }

        return $query->get()->toArray();
    }

    private function getEmployeePerformance(array $dates, ?int $storeId, User $user): array
    {
        [$startDate, $endDate] = $dates;

        $query = DB::table('sales')
            ->join('users', 'sales.cashier_id', '=', 'users.id')
            ->selectRaw('
                users.id,
                users.name,
                COUNT(*) as transaction_count,
                SUM(sales.total) as total_sales,
                AVG(sales.total) as average_transaction,
                SUM(sales.items_count) as total_items_sold
            ')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_sales');

        if ($storeId && $user->canAccessStore($storeId)) {
            $query->where('sales.store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $query->whereIn('sales.store_id', $user->accessibleStores()->pluck('id'));
        }

        return $query->get()->toArray();
    }

    private function getCustomerInsights(array $dates, ?int $storeId, User $user): array
    {
        [$startDate, $endDate] = $dates;

        $baseQuery = DB::table('sales')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($storeId && $user->canAccessStore($storeId)) {
            $baseQuery->where('store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $baseQuery->whereIn('store_id', $user->accessibleStores()->pluck('id'));
        }

        return [
            'total_customers' => $baseQuery->whereNotNull('customer_id')->distinct('customer_id')->count(),
            'new_customers' => DB::table('customers')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),
            'returning_customers' => $baseQuery
                ->whereNotNull('customer_id')
                ->select('customer_id')
                ->groupBy('customer_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count(),
        ];
    }

    private function getStoreComparison(array $dates, User $user): array
    {
        [$startDate, $endDate] = $dates;

        $query = DB::table('sales')
            ->join('stores', 'sales.store_id', '=', 'stores.id')
            ->selectRaw('
                stores.id,
                stores.name,
                COUNT(*) as transaction_count,
                SUM(sales.total) as total_sales,
                AVG(sales.total) as average_transaction
            ')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->groupBy('stores.id', 'stores.name')
            ->orderByDesc('total_sales');

        if (! $user->isAdmin()) {
            $query->whereIn('stores.id', $user->accessibleStores()->pluck('id'));
        }

        return $query->get()->toArray();
    }

    private function getInventoryAnalysis(array $dates, ?int $storeId, User $user): array
    {
        // This would require more complex inventory tracking
        // For now, return basic stock movement data
        [$startDate, $endDate] = $dates;

        $query = DB::table('stock_movements')
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->selectRaw('
                stock_movements.type,
                COUNT(*) as movement_count,
                SUM(ABS(stock_movements.quantity)) as total_quantity
            ')
            ->whereBetween('stock_movements.occurred_at', [$startDate, $endDate])
            ->groupBy('stock_movements.type');

        if ($storeId && $user->canAccessStore($storeId)) {
            $query->where('stock_movements.store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $query->whereIn('stock_movements.store_id', $user->accessibleStores()->pluck('id'));
        }

        return $query->get()->toArray();
    }
}
