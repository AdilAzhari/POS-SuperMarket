<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Common\CreateUserDTOAction;
use App\Actions\Employee\CreateEmployeeAction;
use App\Actions\Employee\ProcessEmployeeDataAction;
use App\Http\Requests\StoreEmployeeRequest;
use App\Models\User;
use App\Services\EmployeeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeService $employeeService,
        private readonly ProcessEmployeeDataAction $processEmployeeDataAction,
        private readonly CreateEmployeeAction $createEmployeeAction,
        private readonly CreateUserDTOAction $createUserDTOAction
    ) {}

    /**
     * Display a listing of employees
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['search', 'role', 'active_only']);
            $data = $this->processEmployeeDataAction->execute($filters);

            return response()->json([
                'data' => $data['employees'],
                'summary' => $data['summary'],
                'analytics' => $data['analytics'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch employees',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created employee
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $employeeDTO = $this->createUserDTOAction->forEmployee($data);

            $employee = $this->createEmployeeAction->execute($employeeDTO);

            return response()->json([
                'data' => $employee,
                'message' => 'Employee created successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to create employee',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified employee
     */
    public function show(User $employee): JsonResponse
    {
        try {
            $performanceData = $this->employeeService->getEmployeePerformance($employee);

            return response()->json([
                'data' => [
                    'employee' => $employee,
                    'performance' => $performanceData,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch employee details',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified employee
     */
    public function update(StoreEmployeeRequest $request, User $employee): JsonResponse
    {
        try {
            //            $this->logInfo('update employee');
            $updatedEmployee = $this->employeeService->updateEmployee($employee, $request->validated());

            return response()->json([
                'data' => $updatedEmployee,
                'message' => 'Employee updated successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to update employee',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate an employee
     */
    public function destroy(User $employee): JsonResponse
    {
        try {
            $this->employeeService->deactivateEmployee($employee);

            return response()->json([
                'message' => 'Employee deactivated successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to deactivate employee',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get employee analytics
     */
    public function analytics(): JsonResponse
    {
        try {
            $analytics = $this->employeeService->getEmployeeAnalytics();

            return response()->json([
                'data' => $analytics,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch analytics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset employee password
     */
    public function resetPassword(Request $request, User $employee): JsonResponse
    {
        try {
            $newPassword = $this->employeeService->resetPassword(
                $employee,
                $request->input('password')
            );

            return response()->json([
                'message' => 'Password reset successfully',
                'new_password' => $newPassword,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to reset password',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available roles and permissions
     */
    public function getRolesAndPermissions(): JsonResponse
    {
        return response()->json([
            'roles' => [
                ['value' => 'admin', 'label' => 'Administrator'],
                ['value' => 'manager', 'label' => 'Manager'],
                ['value' => 'supervisor', 'label' => 'Supervisor'],
                ['value' => 'cashier', 'label' => 'Cashier'],
            ],
            'permissions' => [
                'view_reports' => 'View Reports',
                'manage_inventory' => 'Manage Inventory',
                'manage_employees' => 'Manage Employees',
                'view_analytics' => 'View Analytics',
                'manage_customers' => 'Manage Customers',
                'manage_suppliers' => 'Manage Suppliers',
                'void_sales' => 'Void Sales',
                'apply_discounts' => 'Apply Discounts',
                'create_sales' => 'Create Sales',
                'view_products' => 'View Products',
                'basic_reports' => 'Basic Reports',
            ],
        ]);
    }
}
