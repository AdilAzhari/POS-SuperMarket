<?php

declare(strict_types=1);

namespace App\Actions\Employee;

use App\DTOs\UserDTO;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Support\Facades\Log;

final readonly class CreateEmployeeAction
{
    public function __construct(
        private EmployeeService $employeeService
    ) {}

    /**
     * Create a new employee with validation and setup
     */
    public function execute(UserDTO $employeeDTO): User
    {
        // Generate employee ID if not provided
        if ($employeeDTO->employee_id === null || $employeeDTO->employee_id === '' || $employeeDTO->employee_id === '0') {
            $employeeDTO = new UserDTO(
                name: $employeeDTO->name,
                email: $employeeDTO->email,
                role: $employeeDTO->role,
                is_active: $employeeDTO->is_active,
                employee_id: $this->generateEmployeeId(),
                hourly_rate: $employeeDTO->hourly_rate,
                permissions: $employeeDTO->permissions,
                phone: $employeeDTO->phone,
                address: $employeeDTO->address,
                hire_date: $employeeDTO->hire_date,
                password: $employeeDTO->password
            );
        }

        $employee = $this->employeeService->createEmployee($employeeDTO);

        // Set up default permissions based on role
        $this->setupDefaultPermissions($employee);

        // Log employee creation
        Log::info('[Employee] Created new employee', [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_id,
            'role' => $employee->role->value,
            'created_by' => auth()->id(),
        ]);

        // Generate welcome actions
        $this->scheduleWelcomeActions($employee);

        return $employee->fresh();
    }

    private function generateEmployeeId(): string
    {
        $prefix = 'EMP';
        $year = date('Y');

        // Get next sequence number
        $lastEmployee = User::whereNotNull('employee_id')
            ->where('employee_id', 'like', "{$prefix}-{$year}-%")
            ->orderByDesc('employee_id')
            ->first();

        if ($lastEmployee && preg_match("/EMP-{$year}-(\d+)/", (string) $lastEmployee->employee_id, $matches)) {
            $nextNumber = (int) ($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $year, $nextNumber);
    }

    private function setupDefaultPermissions(User $employee): void
    {
        $defaultPermissions = $employee->role->getPermissions();

        if (! empty($defaultPermissions) && empty($employee->permissions)) {
            $employee->update(['permissions' => $defaultPermissions]);
        }
    }

    private function scheduleWelcomeActions(User $employee): void
    {
        // In a real application, you might queue jobs for:
        // - Sending welcome email
        // - Creating training schedule
        // - Assigning mentor
        // - Setting up equipment requests

        Log::info('[Employee] Scheduled welcome actions', [
            'employee_id' => $employee->id,
            'actions' => [
                'welcome_email' => 'queued',
                'training_setup' => 'pending',
                'equipment_assignment' => 'pending',
            ],
        ]);
    }
}
