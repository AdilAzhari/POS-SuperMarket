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
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed');

        if ($storeId && $user->canAccessStore($storeId)) {
            $baseQuery->where('store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $baseQuery->whereIn('store_id', $user->accessibleStores()->pluck('id'));
        }

        $totalRevenue = $baseQuery->sum('total') ?? 0;
        $totalSales = $baseQuery->count() ?? 0;

        // Get new customers in this period
        $newCustomers = DB::table('customers')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Get category breakdown
        $categoryBreakdown = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('
                COALESCE(categories.name, "Uncategorized") as name,
                SUM(sale_items.line_total) as revenue,
                COUNT(DISTINCT sale_items.id) as sales_count
            ')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->when($storeId && $user->canAccessStore($storeId), fn ($q) => $q->where('sales.store_id', $storeId))
            ->when(! $user->isAdmin() && ! $storeId, fn ($q) => $q->whereIn('sales.store_id', $user->accessibleStores()->pluck('id')))
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(function ($item) use ($totalRevenue) {
                return [
                    'name' => $item->name,
                    'revenue' => (float) $item->revenue,
                    'percentage' => $totalRevenue > 0 ? round(($item->revenue / $totalRevenue) * 100, 2) : 0,
                ];
            })
            ->toArray();

        return [
            'total_revenue' => (float) $totalRevenue,
            'total_sales' => (int) $totalSales,
            'average_sale' => $totalSales > 0 ? round($totalRevenue / $totalSales, 2) : 0,
            'total_items_sold' => $baseQuery->sum('items_count') ?? 0,
            'new_customers' => $newCustomers,
            'category_breakdown' => $categoryBreakdown,
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
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('
                products.id,
                products.name,
                products.sku,
                COALESCE(categories.name, "Uncategorized") as category,
                SUM(sale_items.quantity) as sold_quantity,
                SUM(sale_items.line_total) as revenue,
                COUNT(DISTINCT sales.id) as order_count
            ')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->where('sales.status', 'completed')
            ->groupBy('products.id', 'products.name', 'products.sku', 'categories.name')
            ->orderByDesc('revenue')
            ->limit(20);

        if ($storeId && $user->canAccessStore($storeId)) {
            $query->where('sales.store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $query->whereIn('sales.store_id', $user->accessibleStores()->pluck('id'));
        }

        return $query->get()->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'category' => $item->category,
            'sold_quantity' => (int) $item->sold_quantity,
            'revenue' => (float) $item->revenue,
        ])->toArray();
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
        [$startDate, $endDate] = $dates;

        // Get low stock products
        $lowStockQuery = DB::table('products')
            ->leftJoin('store_product', 'products.id', '=', 'store_product.product_id')
            ->selectRaw('
                products.id,
                products.name,
                products.sku,
                COALESCE(SUM(store_product.quantity), 0) as stock,
                products.low_stock_threshold as lowStockThreshold
            ')
            ->where('products.active', true)
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.low_stock_threshold')
            ->havingRaw('COALESCE(SUM(store_product.quantity), 0) <= products.low_stock_threshold')
            ->orderBy('stock', 'asc')
            ->limit(20);

        if ($storeId && $user->canAccessStore($storeId)) {
            $lowStockQuery->where('store_product.store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $lowStockQuery->whereIn('store_product.store_id', $user->accessibleStores()->pluck('id'));
        }

        $lowStockProducts = $lowStockQuery->get()->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'stock' => (int) $item->stock,
            'lowStockThreshold' => (int) $item->lowStockThreshold,
        ])->toArray();

        // Get stock movements for the period
        $stockMovements = DB::table('stock_movements')
            ->join('products', 'stock_movements.product_id', '=', 'products.id')
            ->selectRaw('
                stock_movements.type,
                COUNT(*) as movement_count,
                SUM(ABS(stock_movements.quantity)) as total_quantity
            ')
            ->whereBetween('stock_movements.occurred_at', [$startDate, $endDate])
            ->when($storeId && $user->canAccessStore($storeId), fn ($q) => $q->where('stock_movements.store_id', $storeId))
            ->when(! $user->isAdmin() && ! $storeId, fn ($q) => $q->whereIn('stock_movements.store_id', $user->accessibleStores()->pluck('id')))
            ->groupBy('stock_movements.type')
            ->get()
            ->toArray();

        return [
            'low_stock_products' => $lowStockProducts,
            'stock_movements' => $stockMovements,
        ];
    }
}
