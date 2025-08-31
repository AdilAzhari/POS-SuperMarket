<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\UserDTO;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Random\RandomException;

final class EmployeeService extends BaseService
{
    protected string $cachePrefix = 'employee:';

    protected int $cacheTime = 3600;

    /**
     * Get all employees with optional filters
     */
    public function getAllEmployees(array $filters = []): Collection
    {
        $query = User::query();

        if (isset($filters['role']) && $filters['role'] !== 'all') {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['active_only']) && $filters['active_only']) {
            $query->where('is_active', true);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('employee_id', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Create a new employee
     */
    public function createEmployee(UserDTO $employeeDTO): User
    {
        $this->logInfo('Creating new employee', ['name' => $employeeDTO->name, 'role' => $employeeDTO->role->value]);

        $data = $employeeDTO->toCreateArray();

        // Generate employee ID if not provided
        if (empty($data['employee_id'])) {
            $data['employee_id'] = $this->generateEmployeeId();
        }

        // Set default permissions based on role
        $data['permissions'] = $this->getDefaultPermissions($employeeDTO->role->value);

        $employee = User::create($data);

        // Clear cache
        $this->forget('all');

        $this->logInfo('Employee created successfully', ['employee_id' => $employee->id]);

        return $employee;
    }

    /**
     * Update an employee
     */
    public function updateEmployee(User $employee, array $data): User
    {
        $this->logInfo('Updating employee', ['employee_id' => $employee->id]);

        // Hash password if provided
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Update permissions if role changed
        if (isset($data['role']) && $data['role'] !== $employee->role) {
            $data['permissions'] = $this->getDefaultPermissions($data['role']);
        }

        $employee->update($data);

        // Clear cache
        $this->forget('all');
        $this->forget("employee:$employee->id");

        $this->logInfo('Employee updated successfully', ['employee_id' => $employee->id]);

        return $employee->fresh();
    }

    /**
     * Deactivate an employee (soft delete)
     */
    public function deactivateEmployee(User $employee): bool
    {
        $this->logInfo('Deactivating employee', ['employee_id' => $employee->id]);

        $employee->update(['is_active' => false]);

        // Clear cache
        $this->forget('all');
        $this->forget("employee:$employee->id");

        $this->logInfo('Employee deactivated successfully', ['employee_id' => $employee->id]);

        return true;
    }

    /**
     * Get employee performance data
     */
    public function getEmployeePerformance(User $employee, $startDate = null, $endDate = null): array
    {
        $startDate = $startDate ?: now()->startOfMonth();
        $endDate = $endDate ?: now()->endOfMonth();

        return $this->remember("performance:$employee->id:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}", function () use ($employee, $startDate, $endDate): array {
            $sales = $employee->sales()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            return [
                'total_sales' => $sales->count(),
                'total_revenue' => $sales->sum('total'),
                'average_sale' => $sales->count() > 0 ? $sales->sum('total') / $sales->count() : 0,
                'total_items_sold' => $sales->sum('items_count'),
                'sales_by_day' => $sales->groupBy(fn ($sale) => $sale->created_at->format('Y-m-d'))->map(fn ($daySales): array => [
                    'count' => $daySales->count(),
                    'revenue' => $daySales->sum('total'),
                ]),
                'busiest_hours' => $sales->groupBy(fn ($sale) => $sale->created_at->format('H'))->map->count(),
            ];
        }, 1800); // Cache for 30 minutes
    }

    /**
     * Get employee analytics summary
     */
    public function getEmployeeAnalytics(): array
    {
        return $this->remember('analytics', function (): array {
            $employees = User::all();

            return [
                'total_employees' => $employees->count(),
                'active_employees' => $employees->where('is_active', true)->count(),
                'by_role' => $employees->groupBy('role')->map->count(),
                'recent_hires' => $employees->where('hire_date', '>=', now()->subDays(30))->count(),
                'average_tenure' => $employees->filter(fn ($emp): bool => $emp->hire_date !== null)->avg(fn ($emp) => $emp->hire_date->diffInDays(now())),
            ];
        }, 3600); // Cache for 1 hour
    }

    /**
     * Reset employee password
     */
    public function resetPassword(User $employee, ?string $newPassword = null): string
    {
        $password = $newPassword !== null && $newPassword !== '' && $newPassword !== '0' ? $newPassword : Str::random(8);

        $employee->update([
            'password' => Hash::make($password),
        ]);

        $this->logInfo('Password reset for employee', ['employee_id' => $employee->id]);

        return $password;
    }

    /**
     * Generate unique employee ID
     *
     * @throws RandomException
     */
    private function generateEmployeeId(): string
    {
        do {
            $employeeId = 'EMP'.mb_str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (User::query()->where('employee_id', $employeeId)->exists());

        return $employeeId;
    }

    /**
     * Get default permissions based on role
     */
    private function getDefaultPermissions(string $role): array
    {
        return match ($role) {
            'admin' => ['*'], // All permissions
            'manager' => [
                'view_reports',
                'manage_inventory',
                'manage_employees',
                'view_analytics',
                'manage_customers',
                'manage_suppliers',
                'void_sales',
                'apply_discounts',
            ],
            'supervisor' => [
                'view_reports',
                'manage_inventory',
                'view_analytics',
                'manage_customers',
                'void_sales',
                'apply_discounts',
            ],
            'cashier' => [
                'create_sales',
                'view_products',
                'manage_customers',
                'basic_reports',
            ],
            default => []
        };
    }
}
