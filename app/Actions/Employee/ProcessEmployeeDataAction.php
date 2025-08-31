<?php

declare(strict_types=1);

namespace App\Actions\Employee;

use App\Services\EmployeeService;
use Illuminate\Support\Collection;

final readonly class ProcessEmployeeDataAction
{
    public function __construct(
        private EmployeeService $employeeService
    ) {}

    /**
     * Process and enhance employee data with computed attributes
     */
    public function execute(array $filters = []): array
    {
        $employees = $this->employeeService->getAllEmployees($filters);

        return [
            'employees' => $this->enhanceEmployeeData($employees),
            'summary' => $this->generateSummary($employees),
            'analytics' => $this->generateAnalytics($employees),
        ];
    }

    private function enhanceEmployeeData(Collection $employees): Collection
    {
        return $employees->map(fn ($employee): array => [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => $employee->role,
            'employee_id' => $employee->employee_id,
            'phone' => $employee->phone,
            'is_active' => $employee->is_active,
            'hourly_rate' => $employee->hourly_rate,
            'hire_date' => $employee->hire_date,
            'last_login_at' => $employee->last_login_at,
            'created_at' => $employee->created_at,
            'permissions' => $employee->permissions,

            // Computed attributes
            'role_label' => $employee->role->getDisplayName(),
            'status_label' => $employee->is_active ? 'Active' : 'Inactive',
            'tenure_days' => $employee->hire_date ? $employee->hire_date->diffInDays(now()) : null,
            'tenure_label' => $this->getTenureLabel($employee),
            'last_activity' => $this->getLastActivity($employee),
            'performance_metrics' => $this->getPerformanceMetrics($employee),
        ]);
    }

    private function generateSummary(Collection $employees): array
    {
        return [
            'total_employees' => $employees->count(),
            'active_employees' => $employees->where('is_active', true)->count(),
            'inactive_employees' => $employees->where('is_active', false)->count(),
            'by_role' => $employees->groupBy('role')->map->count()->toArray(),
            'recent_hires' => $employees->filter(fn ($employee): bool => $employee->hire_date && $employee->hire_date->isAfter(now()->subDays(30)))->count(),
            'average_tenure_days' => $employees->filter(fn ($employee) => $employee->hire_date)->avg(fn ($employee) => $employee->hire_date->diffInDays(now())),
        ];
    }

    private function generateAnalytics(Collection $employees): array
    {
        return [
            'role_distribution' => $this->getRoleDistribution($employees),
            'tenure_analysis' => $this->getTenureAnalysis($employees),
            'activity_patterns' => $this->getActivityPatterns($employees),
            'compensation_insights' => $this->getCompensationInsights($employees),
        ];
    }

    private function getTenureLabel($employee): ?string
    {
        if (! $employee->hire_date) {
            return null;
        }

        $days = $employee->hire_date->diffInDays(now());
        if ($days < 30) {
            return 'New (< 1 month)';
        }
        if ($days < 90) {
            return 'Recent (< 3 months)';
        }
        if ($days < 365) {
            return 'Established (< 1 year)';
        }

        if ($days < 1825) {
            return 'Experienced (1-5 years)';
        }

        return 'Veteran (5+ years)';

    }

    private function getLastActivity($employee): ?array
    {
        if (! $employee->last_login_at) {
            return null;
        }

        return [
            'last_login' => $employee->last_login_at,
            'days_ago' => $employee->last_login_at->diffInDays(now()),
            'status' => $this->getActivityStatus($employee->last_login_at),
        ];
    }

    private function getActivityStatus($lastLogin): string
    {
        $daysAgo = $lastLogin->diffInDays(now());
        if ($daysAgo === 0) {
            return 'active_today';
        }
        if ($daysAgo <= 7) {
            return 'active_week';
        }

        if ($daysAgo <= 30) {
            return 'active_month';
        }

        return 'inactive';

    }

    private function getPerformanceMetrics($employee): array
    {
        // In a real application, this would fetch actual performance data
        return [
            'sales_count' => $employee->sales()->count() ?? 0,
            'sales_total' => $employee->sales()->sum('total') ?? 0,
            'avg_transaction' => $employee->sales()->avg('total') ?? 0,
        ];
    }

    private function getRoleDistribution(Collection $employees): array
    {
        return $employees->groupBy('role')->map(fn ($roleEmployees, $role): array => [
            'count' => $roleEmployees->count(),
            'percentage' => round(($roleEmployees->count() / $employees->count()) * 100, 1),
            'active' => $roleEmployees->where('is_active', true)->count(),
        ])->toArray();
    }

    private function getTenureAnalysis(Collection $employees): array
    {
        $withTenure = $employees->filter(fn ($emp) => $emp->hire_date);

        return [
            'new_hires' => $withTenure->filter(fn ($emp) => $emp->hire_date->isAfter(now()->subDays(90)))->count(),
            'experienced' => $withTenure->filter(fn ($emp) => $emp->hire_date->isBetween(now()->subYears(5), now()->subDays(365)))->count(),
            'veterans' => $withTenure->filter(fn ($emp) => $emp->hire_date->isBefore(now()->subYears(5)))->count(),
            'average_tenure_months' => $withTenure->avg(fn ($emp) => $emp->hire_date->diffInMonths(now())),
        ];
    }

    private function getActivityPatterns(Collection $employees): array
    {
        return [
            'active_today' => $employees->filter(fn ($emp): bool => $emp->last_login_at && $emp->last_login_at->isToday())->count(),
            'active_week' => $employees->filter(fn ($emp): bool => $emp->last_login_at && $emp->last_login_at->isAfter(now()->subWeek()))->count(),
            'inactive_month' => $employees->filter(fn ($emp): bool => ! $emp->last_login_at || $emp->last_login_at->isBefore(now()->subMonth()))->count(),
        ];
    }

    private function getCompensationInsights(Collection $employees): array
    {
        $withRate = $employees->filter(fn ($emp) => $emp->hourly_rate);

        if ($withRate->isEmpty()) {
            return ['no_data' => true];
        }

        return [
            'average_rate' => $withRate->avg('hourly_rate'),
            'min_rate' => $withRate->min('hourly_rate'),
            'max_rate' => $withRate->max('hourly_rate'),
            'by_role' => $withRate->groupBy('role')->map(fn ($roleEmployees): array => [
                'count' => $roleEmployees->count(),
                'average_rate' => $roleEmployees->avg('hourly_rate'),
                'min_rate' => $roleEmployees->min('hourly_rate'),
                'max_rate' => $roleEmployees->max('hourly_rate'),
            ])->toArray(),
        ];
    }
}
