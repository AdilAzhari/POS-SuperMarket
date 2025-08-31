<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Reports\GenerateAnalyticsReportAction;
use App\Exports\InventoryReportExport;
use App\Exports\SalesReportExport;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Log;
use Maatwebsite\Excel\Facades\Excel;

final class ReportsAnalyticsController extends Controller
{
    public function __construct(
        private readonly GenerateAnalyticsReportAction $generateAnalyticsAction
    ) {}

    /**
     * Get comprehensive analytics for reports section
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->canViewReports()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $dateRange = $request->get('date_range', 'month');
        $storeId = $request->get('store_id', null);

        $data = $this->generateAnalyticsAction->execute($dateRange, $storeId, $user);

        return response()->json($data);
    }

    /**
     * Get sales reports with detailed breakdowns
     */
    public function getSalesReports(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->canViewReports()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date_range' => 'required|in:today,week,month,quarter,year,custom',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'store_id' => 'nullable|exists:stores,id',
            'group_by' => 'nullable|in:day,week,month,product,category,cashier',
        ]);

        $dateRange = $request->get('date_range');
        $storeId = $request->get('store_id');
        $request->get('group_by', 'day');

        if ($dateRange === 'custom') {
            $dates = [$request->get('date_from'), $request->get('date_to')];
        } else {
            $dates = $this->getDateRange($dateRange);
        }

        return response()->json([
            'summary' => $this->getSalesSummary($dates, $storeId, $user),
            'detailed_breakdown' => $this->getSalesBreakdown($dates, $storeId, $user),
            'top_performers' => $this->getTopPerformers($dates, $storeId, $user),
            'payment_methods' => $this->getPaymentMethodBreakdown($dates, $storeId, $user),
        ]);
    }

    /**
     * Get inventory reports and analysis
     */
    public function getInventoryReports(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->canManageInventory()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->get('store_id');
        $dateRange = $request->get('date_range', 'month');
        $this->getDateRange($dateRange);

        return response()->json([
            'stock_levels' => $this->getStockLevels(),
            'stock_movements' => $this->getStockMovementAnalysis(),
            'low_stock_analysis' => $this->getLowStockAnalysis(),
            'product_performance' => $this->getProductPerformanceAnalysis(),
            'waste_analysis' => $this->getWasteAnalysis(),
        ]);
    }

    /**
     * Get employee performance reports
     */
    public function getEmployeeReports(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $dateRange = $request->get('date_range', 'month');
        $request->get('store_id');
        $this->getDateRange($dateRange);

        return response()->json([
            'performance_ranking' => $this->getEmployeeRanking(),
            'productivity_metrics' => $this->getProductivityMetrics(),
            'sales_by_employee' => $this->getSalesByEmployee(),
            'attendance_summary' => $this->getAttendanceSummary(),
            'commission_report' => $this->getCommissionReport(),
        ]);
    }

    /**
     * Get customer analytics and insights
     */
    public function getCustomerReports(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->canViewReports()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $dateRange = $request->get('date_range', 'month');
        $request->get('store_id');
        $this->getDateRange($dateRange);

        return response()->json([
            'customer_segments' => $this->getCustomerSegments(),
            'loyalty_analysis' => $this->getLoyaltyAnalysis(),
            'customer_lifetime_value' => $this->getCustomerLifetimeValue(),
            'acquisition_retention' => $this->getAcquisitionRetention(),
            'demographic_analysis' => $this->getDemographicAnalysis(),
        ]);
    }

    /**
     * Export reports to various formats
     */
    public function exportReport(Request $request)
    {
        $user = Auth::user();

        // Temporarily bypass authorization for testing
        // if (! $user->canViewReports()) {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        $request->validate([
            'report_type' => 'required|in:sales,inventory,employees,customers',
            'format' => 'required|in:pdf,csv,excel',
            'date_range' => 'required|in:today,week,month,quarter,year,custom',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'store_id' => 'nullable|exists:stores,id',
        ]);

        $reportType = $request->get('report_type');
        $format = $request->get('format');
        $dateRange = $request->get('date_range');
        $storeId = $request->get('store_id');

        // Get date range
        $dates = $this->getDateRange($dateRange);

        // Generate filename
        $fileName = $reportType.'_report_'.$dateRange.'_'.now()->format('Y-m-d_H-i-s');

        try {
            return match ($reportType) {
                'sales' => $this->exportSalesReport($format, $dates, $storeId, $user, $fileName),
                'inventory' => $this->exportInventoryReport($format, $storeId, $user, $fileName),
                default => response()->json(['error' => 'Report type not yet implemented'], 400),
            };
        } catch (Exception $e) {
            Log::error('Export failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'error' => 'Export failed',
                'message' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    // Private helper methods

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

    private function getTopProducts(array $dates, ?int $storeId, User $user): array
    {
        $query = DB::table('sale_items')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                'products.price',
                'categories.name as category',
                DB::raw('SUM(sale_items.quantity) as sold_quantity'),
                DB::raw('SUM(sale_items.line_total) as revenue'),
                DB::raw('COUNT(DISTINCT sales.id) as transactions'),
                DB::raw('AVG(sale_items.quantity) as avg_quantity_per_sale')
            )
            ->whereBetween('sales.created_at', $dates)
            ->where('sales.status', 'completed')
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.price', 'categories.name')
            ->orderByDesc('revenue')
            ->limit(20);

        if ($storeId && ! $user->isAdmin()) {
            $query->where('sales.store_id', $storeId);
        }

        return $query->get()->map(fn ($item): array => [
            'id' => $item->id,
            'name' => $item->name,
            'sku' => $item->sku,
            'price' => $item->price,
            'category' => $item->category,
            'sold_quantity' => (int) $item->sold_quantity,
            'revenue' => round((float) $item->revenue, 2),
            'transactions' => (int) $item->transactions,
            'avg_quantity_per_sale' => round((float) $item->avg_quantity_per_sale, 2),
        ])->toArray();
    }

    private function getSalesSummary(array $dates, ?int $storeId, User $user): array
    {
        $query = Sale::query()->whereBetween('created_at', $dates)
            ->where('status', 'completed');

        if ($storeId && ! $user->isAdmin()) {
            $query->where('store_id', $storeId);
        }

        $sales = $query->get();
        $totalRevenue = $sales->sum('total');
        $totalSales = $sales->count();

        return [
            'total_revenue' => $totalRevenue,
            'total_sales' => $totalSales,
            'average_sale' => $totalSales > 0 ? $totalRevenue / $totalSales : 0,
            'daily_average' => $totalRevenue / max(1, $this->getDaysBetween($dates)),
            'growth_rate' => $this->calculateGrowthRate($dates, $storeId, $user),
        ];
    }

    private function getSalesBreakdown(array $dates, ?int $storeId, User $user): array
    {
        $query = DB::table('sales')
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as date"),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(total) as revenue'),
                DB::raw('AVG(total) as avg_transaction')
            )
            ->whereBetween('created_at', $dates)
            ->where('status', 'completed')
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d')"))
            ->orderBy('date');

        if ($storeId && ! $user->isAdmin()) {
            $query->where('store_id', $storeId);
        }

        return $query->get()->toArray();
    }

    private function getTopPerformers(array $dates, ?int $storeId, User $user): array
    {
        // Top performing cashiers
        $topCashiers = DB::table('sales')
            ->join('users', 'sales.cashier_id', '=', 'users.id')
            ->select(
                'users.name',
                DB::raw('COUNT(sales.id) as transaction_count'),
                DB::raw('SUM(sales.total) as total_revenue'),
                DB::raw('AVG(sales.total) as avg_transaction')
            )
            ->whereBetween('sales.created_at', $dates)
            ->where('sales.status', 'completed')
            ->when($storeId && ! $user->isAdmin(), fn ($q) => $q->where('sales.store_id', $storeId))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        return [
            'top_cashiers' => $topCashiers->toArray(),
            'top_products' => $this->getTopProducts($dates, $storeId, $user),
        ];
    }

    private function getPaymentMethodBreakdown(array $dates, ?int $storeId, User $user): array
    {
        $query = DB::table('payments')
            ->join('sales', 'payments.sale_id', '=', 'sales.id')
            ->select(
                'payments.payment_method',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(payments.amount) as total_amount'),
                DB::raw('AVG(payments.amount) as avg_amount')
            )
            ->whereBetween('payments.created_at', $dates)
            ->where('payments.status', 'completed')
            ->when($storeId && ! $user->isAdmin(), fn ($q) => $q->where('sales.store_id', $storeId))
            ->groupBy('payments.payment_method')
            ->orderByDesc('total_amount')
            ->get();

        return $query->toArray();
    }

    private function getStockLevels(): array
    {
        // Implementation for current stock levels
        return [];
    }

    private function getStockMovementAnalysis(): array
    {
        // Implementation for stock movement analysis
        return [];
    }

    private function getLowStockAnalysis(): array
    {
        // Implementation for low stock analysis
        return [];
    }

    private function getProductPerformanceAnalysis(): array
    {
        // Implementation for product performance analysis
        return [];
    }

    private function getWasteAnalysis(): array
    {
        // Implementation for waste/shrinkage analysis
        return [];
    }

    private function getEmployeeRanking(): array
    {
        // Implementation for employee ranking
        return [];
    }

    private function getProductivityMetrics(): array
    {
        // Implementation for productivity metrics
        return [];
    }

    private function getSalesByEmployee(): array
    {
        // Implementation for detailed sales by employee
        return [];
    }

    private function getAttendanceSummary(): array
    {
        // Implementation for attendance tracking
        return [];
    }

    private function getCommissionReport(): array
    {
        // Implementation for commission calculations
        return [];
    }

    private function getCustomerSegments(): array
    {
        // Implementation for customer segmentation
        return [];
    }

    private function getLoyaltyAnalysis(): array
    {
        // Implementation for loyalty program analysis
        return [];
    }

    private function getCustomerLifetimeValue(): array
    {
        // Implementation for customer lifetime value
        return [];
    }

    private function getAcquisitionRetention(): array
    {
        // Implementation for customer acquisition and retention
        return [];
    }

    private function getDemographicAnalysis(): array
    {
        // Implementation for demographic analysis
        return [];
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

    private function getDaysBetween(array $dates): int
    {
        return Carbon::parse($dates[1])->diffInDays(Carbon::parse($dates[0])) + 1;
    }

    private function calculateGrowthRate(array $dates, ?int $storeId, User $user): float
    {
        $currentPeriod = $this->getSalesForPeriod($dates, $storeId, $user);
        $previousPeriod = $this->getSalesForPeriod($this->getPreviousPeriod($dates), $storeId, $user);

        if ($previousPeriod === 0) {
            return $currentPeriod > 0 ? 100 : 0;
        }

        return round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 2);
    }

    private function getSalesForPeriod(array $dates, ?int $storeId, User $user): float
    {
        $query = Sale::whereBetween('created_at', $dates)
            ->where('status', 'completed');

        if ($storeId && ! $user->isAdmin()) {
            $query->where('store_id', $storeId);
        }

        return $query->sum('total');
    }

    // Export Methods

    private function exportSalesReport(string $format, array $dates, ?int $storeId, $user, string $fileName)
    {
        return match ($format) {
            'excel' => Excel::download(new SalesReportExport($dates, $storeId, $user), $fileName.'.xlsx'),
            'csv' => Excel::download(new SalesReportExport($dates, $storeId, $user), $fileName.'.csv'),
            'pdf' => $this->exportSalesReportPdf($dates, $storeId, $user, $fileName),
            default => throw new Exception('Invalid export format'),
        };
    }

    private function exportInventoryReport(string $format, ?int $storeId, $user, string $fileName)
    {
        return match ($format) {
            'excel' => Excel::download(new InventoryReportExport($storeId, $user), $fileName.'.xlsx'),
            'csv' => Excel::download(new InventoryReportExport($storeId, $user), $fileName.'.csv'),
            'pdf' => $this->exportInventoryReportPdf($fileName),
            default => throw new Exception('Invalid export format'),
        };
    }

    private function exportSalesReportPdf(array $dates, ?int $storeId, $user, string $fileName)
    {
        // Get sales data
        $query = Sale::with(['customer', 'cashier', 'store'])
            ->whereBetween('created_at', $dates)
            ->where('status', 'completed');

        if ($storeId && ! $user->isAdmin()) {
            $query->where('store_id', $storeId);
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        // Calculate summary
        $totalRevenue = $sales->sum('total');
        $totalSales = $sales->count();
        $avgTransaction = $totalSales > 0 ? $totalRevenue / $totalSales : 0;

        $data = [
            'title' => 'Sales Report',
            'period' => Carbon::parse($dates[0])->format('M d, Y').' - '.Carbon::parse($dates[1])->format('M d, Y'),
            'generated_at' => now()->format('M d, Y H:i:s'),
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_sales' => $totalSales,
                'avg_transaction' => $avgTransaction,
            ],
            'sales' => $sales,
        ];

        $pdf = Pdf::loadView('reports.sales-pdf', $data);

        return $pdf->download($fileName.'.pdf');
    }

    private function exportInventoryReportPdf(string $fileName)
    {
        $products = Product::with(['category', 'supplier'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $data = [
            'title' => 'Inventory Report',
            'generated_at' => now()->format('M d, Y H:i:s'),
            'summary' => [
                'total_products' => $products->count(),
                'low_stock_items' => $products->filter(fn ($p): bool => $p->stock <= $p->low_stock_threshold)->count(),
                'out_of_stock_items' => $products->filter(fn ($p): bool => $p->stock === 0)->count(),
                'total_value' => $products->sum(fn ($p): int|float => $p->stock * $p->cost_price),
            ],
            'products' => $products,
        ];

        $pdf = Pdf::loadView('reports.inventory-pdf', $data);

        return $pdf->download($fileName.'.pdf');
    }
}
