<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManagerDashboardController extends Controller
{
    public function getAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->canViewReports()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $dateRange = $request->get('date_range', 'month');
        $storeId = $request->get('store_id', null);

        // Get date range
        $dates = $this->getDateRange($dateRange);

        $data = [
            'overview' => $this->getOverviewMetrics($dates, $storeId, $user),
            'sales_trend' => $this->getSalesTrend($dates, $storeId, $user),
            'top_products' => $this->getTopProducts($dates, $storeId, $user),
            'employee_performance' => $this->getEmployeePerformance($dates, $storeId, $user),
            'inventory_alerts' => $this->getInventoryAlerts($storeId, $user),
            'customer_insights' => $this->getCustomerInsights($dates, $storeId, $user),
            'store_comparison' => $this->getStoreComparison($dates, $user),
        ];

        return response()->json($data);
    }

    public function getRealtimeStats(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->canViewReports()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $storeId = $request->get('store_id', null);
        $today = now()->startOfDay();

        $stats = [
            'today_sales' => $this->getTodaySales($storeId, $user),
            'active_employees' => $this->getActiveEmployees($storeId, $user),
            'pending_orders' => $this->getPendingOrders($storeId, $user),
            'low_stock_count' => $this->getLowStockCount($storeId, $user),
            'hourly_sales' => $this->getHourlySales($storeId, $user),
        ];

        return response()->json($stats);
    }

    public function getEmployeeMetrics(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $dateRange = $request->get('date_range', 'month');
        $storeId = $request->get('store_id', null);
        $dates = $this->getDateRange($dateRange);

        $metrics = [
            'performance_ranking' => $this->getEmployeeRanking($dates, $storeId),
            'attendance_summary' => $this->getAttendanceSummary($dates, $storeId),
            'sales_by_employee' => $this->getSalesByEmployee($dates, $storeId),
            'productivity_metrics' => $this->getProductivityMetrics($dates, $storeId),
        ];

        return response()->json($metrics);
    }

    private function getDateRange(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()],
            'week' => [now()->startOfWeek(), now()],
            'month' => [now()->startOfMonth(), now()],
            'quarter' => [now()->startOfQuarter(), now()],
            'year' => [now()->startOfYear(), now()],
            default => [now()->startOfMonth(), now()],
        };
    }

    private function getOverviewMetrics(array $dates, ?int $storeId, User $user): array
    {
        $query = Sale::whereBetween('created_at', $dates)->where('status', 'completed');

        if ($storeId && ! $user->isAdmin()) {
            $query->where('store_id', $storeId);
        }

        $sales = $query->get();
        $totalRevenue = $sales->sum('total');
        $totalSales = $sales->count();
        $averageSale = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        // Get previous period for comparison
        $prevPeriod = $this->getPreviousPeriod($dates);
        $prevQuery = Sale::whereBetween('created_at', $prevPeriod)->where('status', 'completed');

        if ($storeId && ! $user->isAdmin()) {
            $prevQuery->where('store_id', $storeId);
        }

        $prevSales = $prevQuery->get();
        $prevRevenue = $prevSales->sum('total');
        $prevCount = $prevSales->count();

        return [
            'total_revenue' => round($totalRevenue, 2),
            'total_sales' => $totalSales,
            'average_sale' => round($averageSale, 2),
            'revenue_growth' => $this->calculateGrowth($totalRevenue, $prevRevenue),
            'sales_growth' => $this->calculateGrowth($totalSales, $prevCount),
            'new_customers' => Customer::whereBetween('created_at', $dates)->count(),
        ];
    }

    private function getSalesTrend(array $dates, ?int $storeId, User $user): array
    {
        $query = Sale::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(total) as revenue'),
            DB::raw('COUNT(*) as count')
        )
            ->whereBetween('created_at', $dates)
            ->where('status', 'completed')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date');

        if ($storeId && ! $user->isAdmin()) {
            $query->where('store_id', $storeId);
        }

        return $query->get()->toArray();
    }

    private function getTopProducts(array $dates, ?int $storeId, User $user): array
    {
        $query = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.price',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.line_total) as total_revenue')
            )
            ->whereBetween('sales.created_at', $dates)
            ->where('sales.status', 'completed')
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.price')
            ->orderByDesc('total_quantity')
            ->limit(10);

        if ($storeId && ! $user->isAdmin()) {
            $query->where('sales.store_id', $storeId);
        }

        return $query->get()->toArray();
    }

    private function getEmployeePerformance(array $dates, ?int $storeId, User $user): array
    {
        if (! $user->canManageUsers()) {
            return [];
        }

        $query = DB::table('sales')
            ->join('users', 'sales.cashier_id', '=', 'users.id')
            ->select(
                'users.id',
                'users.name',
                'users.role',
                DB::raw('COUNT(sales.id) as sales_count'),
                DB::raw('SUM(sales.total) as total_revenue'),
                DB::raw('AVG(sales.total) as avg_sale')
            )
            ->whereBetween('sales.created_at', $dates)
            ->where('sales.status', 'completed')
            ->groupBy('users.id', 'users.name', 'users.role')
            ->orderByDesc('total_revenue');

        if ($storeId && ! $user->isAdmin()) {
            $query->where('sales.store_id', $storeId);
        }

        return $query->get()->toArray();
    }

    private function getInventoryAlerts(?int $storeId, User $user): array
    {
        if (! $user->canManageInventory()) {
            return [];
        }

        $query = Product::select('id', 'name', 'sku', 'stock', 'low_stock_threshold')
            ->whereRaw('stock <= low_stock_threshold')
            ->where('is_active', true)
            ->orderBy('stock', 'asc')
            ->limit(20);

        // For now, we don't filter by store as products are global
        // In future, you might want to add store-specific stock levels

        return $query->get()->toArray();
    }

    private function getCustomerInsights(array $dates, ?int $storeId, User $user): array
    {
        $query = DB::table('customers')
            ->leftJoin('sales', 'customers.id', '=', 'sales.customer_id')
            ->select(
                DB::raw('COUNT(DISTINCT customers.id) as total_customers'),
                DB::raw('COUNT(DISTINCT CASE WHEN sales.created_at BETWEEN ? AND ? THEN customers.id END) as active_customers'),
                DB::raw('AVG(customers.total_spent) as avg_customer_value'),
                DB::raw('COUNT(sales.id) as total_transactions')
            );

        if ($storeId && ! $user->isAdmin()) {
            $query->where('sales.store_id', $storeId);
        }

        $result = $query->setBindings($dates)->first();

        return [
            'total_customers' => $result->total_customers ?? 0,
            'active_customers' => $result->active_customers ?? 0,
            'avg_customer_value' => round($result->avg_customer_value ?? 0, 2),
            'customer_retention' => $result->total_customers > 0
                ? round(($result->active_customers / $result->total_customers) * 100, 1)
                : 0,
        ];
    }

    private function getStoreComparison(array $dates, User $user): array
    {
        if (! $user->isAdmin()) {
            return [];
        }

        return DB::table('sales')
            ->join('stores', 'sales.store_id', '=', 'stores.id')
            ->select(
                'stores.id',
                'stores.name',
                DB::raw('COUNT(sales.id) as sales_count'),
                DB::raw('SUM(sales.total) as revenue'),
                DB::raw('AVG(sales.total) as avg_sale')
            )
            ->whereBetween('sales.created_at', $dates)
            ->where('sales.status', 'completed')
            ->groupBy('stores.id', 'stores.name')
            ->orderByDesc('revenue')
            ->get()
            ->toArray();
    }

    private function getTodaySales(?int $storeId, User $user): array
    {
        $today = now()->startOfDay();
        $query = Sale::where('created_at', '>=', $today)
            ->where('status', 'completed');

        if ($storeId && ! $user->isAdmin()) {
            $query->where('store_id', $storeId);
        }

        $sales = $query->get();

        return [
            'count' => $sales->count(),
            'revenue' => $sales->sum('total'),
            'avg_sale' => $sales->count() > 0 ? $sales->avg('total') : 0,
        ];
    }

    private function getActiveEmployees(?int $storeId, User $user): int
    {
        $query = User::where('is_active', true)
            ->where('role', '!=', 'customer');

        if ($storeId && ! $user->isAdmin()) {
            // You might want to add store_id to users table for filtering
            // For now, count all active employees
        }

        return $query->count();
    }

    private function getPendingOrders(?int $storeId, User $user): int
    {
        $query = Sale::where('status', 'pending');

        if ($storeId && ! $user->isAdmin()) {
            $query->where('store_id', $storeId);
        }

        return $query->count();
    }

    private function getLowStockCount(?int $storeId, User $user): int
    {
        if (! $user->canManageInventory()) {
            return 0;
        }

        return Product::whereRaw('stock <= low_stock_threshold')
            ->where('is_active', true)
            ->count();
    }

    private function getHourlySales(?int $storeId, User $user): array
    {
        $today = now()->startOfDay();
        $query = Sale::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total) as revenue')
        )
            ->where('created_at', '>=', $today)
            ->where('status', 'completed')
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour');

        if ($storeId && ! $user->isAdmin()) {
            $query->where('store_id', $storeId);
        }

        return $query->get()->toArray();
    }

    private function getPreviousPeriod(array $dates): array
    {
        $start = Carbon::parse($dates[0]);
        $end = Carbon::parse($dates[1]);
        $duration = $start->diffInDays($end);

        return [
            $start->copy()->subDays($duration + 1),
            $start->copy()->subDay(),
        ];
    }

    private function calculateGrowth(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getEmployeeRanking(array $dates, ?int $storeId): array
    {
        // Implementation for employee ranking based on sales performance
        return [];
    }

    private function getAttendanceSummary(array $dates, ?int $storeId): array
    {
        // Implementation for attendance tracking
        return [];
    }

    private function getSalesByEmployee(array $dates, ?int $storeId): array
    {
        // Implementation for detailed sales by employee
        return [];
    }

    private function getProductivityMetrics(array $dates, ?int $storeId): array
    {
        // Implementation for productivity metrics
        return [];
    }
}
