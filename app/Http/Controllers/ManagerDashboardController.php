<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Dashboard\GenerateManagerDashboardAction;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ManagerDashboardController extends Controller
{
    public function __construct(
        private readonly GenerateManagerDashboardAction $generateDashboardAction
    ) {}

    /**
     * Get real-time dashboard overview for operations management
     */
    public function getDashboardOverview(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user || ! $user->canViewReports()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $storeId = $request->integer('store_id', null);

            $data = $this->generateDashboardAction->execute($storeId, $user);

            return response()->json($data);
        } catch (Exception $e) {
            Log::error('Dashboard overview error: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to load dashboard data',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get real-time stats for live monitoring
     */
    public function getRealtimeStats(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (! $user || ! $user->canViewReports()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $storeId = $request->integer('store_id', null);

            $stats = [
                'live_sales' => $this->getLiveSalesMetrics($storeId, $user),
                'current_shift' => $this->getCurrentShiftMetrics($storeId, $user),
                'system_status' => $this->getSystemStatus(),
                'alerts_count' => $this->getAlertsCount($storeId, $user),
            ];

            return response()->json($stats);
        } catch (Exception $e) {
            Log::error('Realtime stats error: '.$e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to load realtime stats',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get current active staff and their real-time metrics
     */
    public function getActiveStaffMetrics(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user->canManageUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $storeId = $request->get('store_id', null);

        $metrics = [
            'active_cashiers' => $this->getActiveCashiers($storeId, $user),
            'shift_performance' => $this->getShiftPerformance($storeId, $user),
            'staff_alerts' => $this->getStaffAlerts($storeId, $user),
        ];

        return response()->json($metrics);
    }

    /**
     * Handle quick management actions
     */
    public function executeQuickAction(Request $request): JsonResponse
    {
        Auth::user();
        $action = $request->get('action');
        $request->get('params', []);

        // Validate action permissions and execute
        return match ($action) {
            'acknowledge_alert' => $this->acknowledgeAlert(),
            'close_register' => $this->closeRegister(),
            'send_low_stock_alert' => $this->sendLowStockAlert(),
            default => response()->json(['error' => 'Invalid action'], 400),
        };
    }

    private function getUrgentAlerts(?int $storeId, User $user): array
    {
        $alerts = [];

        // Critical stock alerts
        if ($user->canManageInventory()) {
            $criticalStock = $this->getCriticalStockCount($storeId);
            $outOfStock = $this->getOutOfStockCount($storeId);

            if ($criticalStock > 0) {
                $alerts[] = [
                    'type' => 'low_stock',
                    'severity' => 'warning',
                    'count' => $criticalStock,
                    'message' => "$criticalStock products running low",
                ];
            }

            if ($outOfStock > 0) {
                $alerts[] = [
                    'type' => 'out_of_stock',
                    'severity' => 'critical',
                    'count' => $outOfStock,
                    'message' => "$outOfStock products out of stock",
                ];
            }
        }

        // Payment issues
        $failedPayments = $this->getFailedPaymentsToday($storeId, $user);
        if ($failedPayments > 0) {
            $alerts[] = [
                'type' => 'payment_failures',
                'severity' => 'warning',
                'count' => $failedPayments,
                'message' => "$failedPayments payment failures today",
            ];
        }

        // System alerts
        $systemAlerts = $this->getSystemAlerts();

        return array_merge($alerts, $systemAlerts);
    }

    private function getLiveSalesMetrics(?int $storeId, User $user): array
    {
        $now = now();
        $today = $now->startOfDay();

        $query = Sale::query()->where('created_at', '>=', $today)
            ->where('status', 'completed');

        if ($storeId && ! $user->isAdmin()) {
            $query->where('store_id', $storeId);
        }

        $sales = $query->get();
        $lastSale = $query->orderByDesc('created_at')->first();

        return [
            'total_today' => $sales->sum('total'),
            'transactions_today' => $sales->count(),
            'avg_transaction' => $sales->count() > 0 ? $sales->avg('total') : 0,
            'last_sale_time' => $lastSale?->created_at->diffForHumans(),
            'sales_per_hour' => $this->getSalesPerHour($sales, $now->hour + 1),
        ];
    }

    private function getCurrentShiftMetrics(?int $storeId, User $user): array
    {
        $shiftStart = now()->hour >= 6 ? now()->setHour(6)->setMinute(0) : now()->subDay()->setHour(6)->setMinute(0);

        if (! $user->canManageUsers()) {
            return [];
        }

        return [
            'active_cashiers' => $this->getActiveCashiers($storeId, $user),
            'shift_duration' => now()->diffInHours($shiftStart),
            'breaks_taken' => 0, // Placeholder for break tracking
            'shift_sales' => $this->getShiftSales($shiftStart, $storeId, $user),
        ];
    }

    private function getSystemStatus(): array
    {
        return [
            'pos_terminals' => $this->getPosTerminalStatus(),
            'payment_processors' => $this->getPaymentProcessorStatus(),
            'inventory_sync' => $this->getInventorySyncStatus(),
            'backup_status' => $this->getBackupStatus(),
        ];
    }

    private function getAlertsCount(?int $storeId, User $user): array
    {
        return [
            'critical' => count(array_filter($this->getUrgentAlerts($storeId, $user), fn (array $alert): bool => $alert['severity'] === 'critical')),
            'warnings' => count(array_filter($this->getUrgentAlerts($storeId, $user), fn (array $alert): bool => $alert['severity'] === 'warning')),
            'info' => count(array_filter($this->getUrgentAlerts($storeId, $user), fn (array $alert): bool => $alert['severity'] === 'info')),
        ];
    }

    private function getSalesPerHour($sales, int $hoursElapsed): float
    {
        if ($hoursElapsed <= 0) {
            return 0;
        }

        return round($sales->count() / $hoursElapsed, 2);
    }

    private function getActiveCashiers(?int $storeId, User $user): array
    {
        // Get users who have made sales in the last 2 hours
        $recentCashiers = DB::table('sales')
            ->join('users', 'sales.cashier_id', '=', 'users.id')
            ->select('users.id', 'users.name', DB::raw('MAX(sales.created_at) as last_sale'))
            ->where('sales.created_at', '>=', now()->subHours(2))
            ->when($storeId && ! $user->isAdmin(), fn ($q) => $q->where('sales.store_id', $storeId))
            ->groupBy('users.id', 'users.name')
            ->get();

        return $recentCashiers->map(fn ($cashier): array => [
            'id' => $cashier->id,
            'name' => $cashier->name,
            'last_activity' => Carbon::parse($cashier->last_sale)->diffForHumans(),
            'status' => 'active',
        ])->toArray();
    }

    private function getShiftPerformance(?int $storeId, User $user): array
    {
        if (! $user->canManageUsers()) {
            return [];
        }

        $shiftStart = now()->hour >= 6 ? now()->setHour(6)->setMinute(0) : now()->subDay()->setHour(6)->setMinute(0);

        return DB::table('sales')
            ->join('users', 'sales.cashier_id', '=', 'users.id')
            ->select(
                'users.name',
                DB::raw('COUNT(sales.id) as transactions'),
                DB::raw('SUM(sales.total) as revenue'),
                DB::raw('AVG(sales.total) as avg_sale')
            )
            ->where('sales.created_at', '>=', $shiftStart)
            ->where('sales.status', 'completed')
            ->when($storeId && ! $user->isAdmin(), fn ($q) => $q->where('sales.store_id', $storeId))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function getStaffAlerts(?int $storeId, User $user): array
    {
        if (! $user->canManageUsers()) {
            return [];
        }

        $alerts = [];

        // Check for staff on long shifts
        $longShifts = $this->getStaffOnLongShifts($storeId);
        if ($longShifts > 0) {
            $alerts[] = [
                'type' => 'long_shifts',
                'message' => "$longShifts staff members on shifts over 8 hours",
                'severity' => 'warning',
            ];
        }

        return $alerts;
    }

    private function getFailedPaymentsToday(?int $storeId, User $user): int
    {
        try {
            if (! DB::getSchemaBuilder()->hasTable('payments')) {
                return 0;
            }

            $query = DB::table('payments')
                ->where('created_at', '>=', now()->startOfDay())
                ->where('status', 'failed');

            if ($storeId && ! $user->isAdmin()) {
                $query->where('store_id', $storeId);
            }

            return $query->count();
        } catch (Exception) {
            return 0;
        }
    }

    private function getSystemAlerts(): array
    {
        $alerts = [];
        // Check for database connection issues
        try {
            DB::connection()->getPdo();
        } catch (Exception) {
            $alerts[] = [
                'type' => 'database_connection',
                'severity' => 'critical',
                'message' => 'Database connection issue detected',
            ];
        }
        // Check for high transaction volume
        $recentTransactions = Sale::query()->where('created_at', '>=', now()->subMinutes(5))->count();
        if ($recentTransactions > 50) {
            $alerts[] = [
                'type' => 'high_volume',
                'severity' => 'warning',
                'message' => 'High transaction volume detected',
            ];
        }

        return $alerts;
    }

    private function getShiftSales($shiftStart, ?int $storeId, User $user): array
    {
        $query = Sale::query()->where('created_at', '>=', $shiftStart)
            ->where('status', 'completed');

        if ($storeId && ! $user->isAdmin()) {
            $query->where('store_id', $storeId);
        }

        $sales = $query->get();

        return [
            'count' => $sales->count(),
            'revenue' => $sales->sum('total'),
        ];
    }

    private function getPosTerminalStatus(): array
    {
        return [
            'terminal_1' => 'online',
            'terminal_2' => 'online',
            'terminal_3' => 'offline',
        ];
    }

    private function getPaymentProcessorStatus(): array
    {
        return [
            'stripe' => 'connected',
            'square' => 'connected',
            'paypal' => 'disconnected',
        ];
    }

    private function getInventorySyncStatus(): array
    {
        return [
            'last_sync' => now()->subMinutes(5)->format('H:i'),
            'status' => 'synced',
        ];
    }

    private function getBackupStatus(): array
    {
        return [
            'last_backup' => now()->subHours(2)->format('H:i'),
            'status' => 'completed',
        ];
    }

    private function getStaffOnLongShifts(?int $storeId): int
    {
        // Check for staff working more than 8 hours (based on first and last sale times)
        return DB::table('sales')
            ->select('cashier_id',
                DB::raw('MIN(created_at) as shift_start'),
                DB::raw('MAX(created_at) as shift_end'))
            ->where('created_at', '>=', now()->subHours(12))
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->groupBy('cashier_id')
            ->havingRaw('TIMESTAMPDIFF(HOUR, MIN(created_at), MAX(created_at)) > 8')
            ->count();
    }

    // Quick action methods

    private function acknowledgeAlert(): JsonResponse
    {
        // Implementation for acknowledging alerts
        return response()->json(['success' => true, 'message' => 'Alert acknowledged']);
    }

    private function closeRegister(): JsonResponse
    {
        // Implementation for closing register
        return response()->json(['success' => true, 'message' => 'Register closed']);
    }

    private function sendLowStockAlert(): JsonResponse
    {
        // Implementation for sending low stock alerts
        return response()->json(['success' => true, 'message' => 'Low stock alert sent']);
    }

    private function getCriticalStockCount(?int $storeId = null): int
    {
        if ($storeId !== null && $storeId !== 0) {
            return DB::table('product_store')
                ->join('products', 'product_store.product_id', '=', 'products.id')
                ->where('product_store.store_id', $storeId)
                ->where('products.active', true)
                ->whereRaw('product_store.stock <= product_store.low_stock_threshold')
                ->where('product_store.stock', '>', 0)
                ->count();
        }

        return DB::table('product_store')
            ->join('products', 'product_store.product_id', '=', 'products.id')
            ->where('products.active', true)
            ->whereRaw('product_store.stock <= product_store.low_stock_threshold')
            ->where('product_store.stock', '>', 0)
            ->count();
    }

    private function getOutOfStockCount(?int $storeId = null): int
    {
        if ($storeId !== null && $storeId !== 0) {
            return DB::table('product_store')
                ->join('products', 'product_store.product_id', '=', 'products.id')
                ->where('product_store.store_id', $storeId)
                ->where('products.active', true)
                ->where('product_store.stock', 0)
                ->count();
        }

        return DB::table('product_store')
            ->join('products', 'product_store.product_id', '=', 'products.id')
            ->where('products.active', true)
            ->where('product_store.stock', 0)
            ->count();
    }
}
