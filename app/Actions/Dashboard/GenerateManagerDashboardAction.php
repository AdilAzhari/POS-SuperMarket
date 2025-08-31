<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class GenerateManagerDashboardAction
{
    /**
     * Generate real-time dashboard overview for operations management
     */
    public function execute(?int $storeId, User $user): array
    {
        return [
            'today_metrics' => $this->getTodayMetrics($storeId, $user),
            'live_stats' => $this->getLiveStats($storeId, $user),
            'urgent_alerts' => $this->getUrgentAlerts($storeId, $user),
            'quick_actions' => $this->getQuickActions($user),
            'recent_activity' => $this->getRecentActivity($storeId, $user),
        ];
    }

    private function getTodayMetrics(?int $storeId, User $user): array
    {
        $today = Carbon::today();
        $query = Sale::query()->whereDate('created_at', $today);

        if ($storeId && $user->canAccessStore($storeId)) {
            $query->where('store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $query->whereIn('store_id', $user->accessibleStores()->pluck('id'));
        }

        return [
            'total_sales' => $query->sum('total'),
            'transaction_count' => $query->count(),
            'average_transaction' => $query->avg('total') ?: 0,
            'items_sold' => $query->sum('items_count'),
            'compared_to_yesterday' => $this->compareToYesterday($storeId, $user),
        ];
    }

    private function getLiveStats(?int $storeId, User $user): array
    {
        $lastHour = Carbon::now()->subHour();
        $query = Sale::query()->where('created_at', '>=', $lastHour);

        if ($storeId && $user->canAccessStore($storeId)) {
            $query->where('store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $query->whereIn('store_id', $user->accessibleStores()->pluck('id'));
        }

        return [
            'last_hour_sales' => $query->sum('total'),
            'last_hour_transactions' => $query->count(),
            'current_hour_trend' => $this->getCurrentHourTrend($storeId, $user),
            'active_cashiers' => $this->getActiveCashiers($storeId, $user),
        ];
    }

    private function getUrgentAlerts(?int $storeId, User $user): array
    {
        $alerts = [];

        // Low stock alerts
        $lowStockQuery = DB::table('product_store')
            ->join('products', 'product_store.product_id', '=', 'products.id')
            ->join('stores', 'product_store.store_id', '=', 'stores.id')
            ->whereRaw('product_store.stock <= product_store.low_stock_threshold');

        if ($storeId && $user->canAccessStore($storeId)) {
            $lowStockQuery->where('stores.id', $storeId);
        }

        $lowStockCount = $lowStockQuery->count();
        if ($lowStockCount > 0) {
            $alerts[] = [
                'type' => 'low_stock',
                'severity' => 'warning',
                'message' => "$lowStockCount products are running low on stock",
                'count' => $lowStockCount,
                'action_url' => '/inventory/alerts',
            ];
        }

        // Recent failed transactions
        $failedTransactions = Sale::query()->where('status', 'failed')
            ->where('created_at', '>=', Carbon::now()->subHours(4))
            ->count();

        if ($failedTransactions > 0) {
            $alerts[] = [
                'type' => 'failed_transactions',
                'severity' => 'error',
                'message' => "$failedTransactions transactions failed in the last 4 hours",
                'count' => $failedTransactions,
                'action_url' => '/sales?status=failed',
            ];
        }

        return $alerts;
    }

    private function getQuickActions(User $user): array
    {
        $actions = [];

        if ($user->canManageInventory()) {
            $actions[] = [
                'id' => 'stock_adjustment',
                'label' => 'Stock Adjustment',
                'icon' => 'inventory',
                'url' => '/stock-movements/create',
                'color' => 'blue',
            ];

            $actions[] = [
                'id' => 'reorder_products',
                'label' => 'Reorder Products',
                'icon' => 'shopping-cart',
                'url' => '/reorder',
                'color' => 'green',
            ];
        }

        if ($user->canViewReports()) {
            $actions[] = [
                'id' => 'daily_report',
                'label' => 'Daily Report',
                'icon' => 'chart',
                'url' => '/reports/daily',
                'color' => 'purple',
            ];
        }

        return $actions;
    }

    private function getRecentActivity(?int $storeId, User $user): array
    {
        $activities = [];

        // Recent sales
        $salesQuery = Sale::with(['customer', 'user'])
            ->latest()
            ->take(5);

        if ($storeId && $user->canAccessStore($storeId)) {
            $salesQuery->where('store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $salesQuery->whereIn('store_id', $user->accessibleStores()->pluck('id'));
        }

        $recentSales = $salesQuery->get();
        foreach ($recentSales as $sale) {
            $activities[] = [
                'type' => 'sale',
                'description' => "Sale #$sale->code - $".number_format($sale->total, 2),
                'user' => $sale->user?->name ?? 'System',
                'timestamp' => $sale->created_at,
                'metadata' => [
                    'customer' => $sale->customer?->name,
                    'items_count' => $sale->items_count,
                ],
            ];
        }

        // Recent stock movements
        $stockMovements = StockMovement::with(['product', 'user'])
            ->latest()
            ->take(3)
            ->get();

        foreach ($stockMovements as $movement) {
            $activities[] = [
                'type' => 'stock_movement',
                'description' => ucfirst((string) $movement->type->value)." - {$movement->product->name} ($movement->quantity)",
                'user' => $movement->user?->name ?? 'System',
                'timestamp' => $movement->created_at,
                'metadata' => [
                    'reason' => $movement->reason->value,
                    'quantity' => $movement->quantity,
                ],
            ];
        }

        // Sort by timestamp
        usort($activities, fn (array $a, array $b): int => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($activities, 0, 8);
    }

    private function compareToYesterday(?int $storeId, User $user): array
    {
        $yesterday = Carbon::yesterday();
        $query = Sale::query()->whereDate('created_at', $yesterday);

        if ($storeId && $user->canAccessStore($storeId)) {
            $query->where('store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $query->whereIn('store_id', $user->accessibleStores()->pluck('id'));
        }

        $yesterdaySales = $query->sum('total');
        $todaySales = $this->getTodayMetrics($storeId, $user)['total_sales'];

        $percentChange = $yesterdaySales > 0
            ? (($todaySales - $yesterdaySales) / $yesterdaySales) * 100
            : 0;

        return [
            'yesterday_total' => $yesterdaySales,
            'percent_change' => round($percentChange, 1),
            'trend' => $percentChange > 0 ? 'up' : ($percentChange < 0 ? 'down' : 'flat'),
        ];
    }

    private function getCurrentHourTrend(?int $storeId, User $user): array
    {
        $currentHour = Carbon::now()->startOfHour();
        $previousHour = Carbon::now()->subHour()->startOfHour();

        $currentQuery = Sale::query()->whereBetween('created_at', [$currentHour, Carbon::now()]);
        $previousQuery = Sale::query()->whereBetween('created_at', [$previousHour, $currentHour]);

        if ($storeId && $user->canAccessStore($storeId)) {
            $currentQuery->where('store_id', $storeId);
            $previousQuery->where('store_id', $storeId);
        } elseif (! $user->isAdmin()) {
            $stores = $user->accessibleStores()->pluck('id');
            $currentQuery->whereIn('store_id', $stores);
            $previousQuery->whereIn('store_id', $stores);
        }

        $currentHourSales = $currentQuery->sum('total');
        $previousHourSales = $previousQuery->sum('total');

        return [
            'current_hour' => $currentHourSales,
            'previous_hour' => $previousHourSales,
            'trend' => $currentHourSales > $previousHourSales ? 'up' : 'down',
        ];
    }

    private function getActiveCashiers(?int $storeId, User $user): array
    {
        $since = Carbon::now()->subHours(2);
        $query = User::query()->whereHas('sales', function ($q) use ($since): void {
            $q->where('created_at', '>=', $since);
        });

        if ($storeId && $user->canAccessStore($storeId)) {
            $query->whereHas('sales', function ($q) use ($storeId): void {
                $q->where('store_id', $storeId);
            });
        }

        return $query->with(['sales' => function ($q) use ($since): void {
            $q->where('created_at', '>=', $since);
        }])->get()->map(fn ($cashier): array => [
            'id' => $cashier->id,
            'name' => $cashier->name,
            'recent_transactions' => $cashier->sales->count(),
            'recent_sales_total' => $cashier->sales->sum('total'),
        ])->toArray();
    }
}
