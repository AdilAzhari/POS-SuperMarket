<?php

declare(strict_types=1);

namespace App\Actions\Common;

use App\DTOs\UserDTO;
use App\Enums\UserRole;
use App\Models\User;

final class CreateUserDTOAction
{
    /**
     * Create UserDTO from array data for new user creation
     */
    public function fromArray(array $data): UserDTO
    {
        return new UserDTO(
            name: $data['name'],
            email: $data['email'],
            role: UserRole::from($data['role']),
            is_active: $data['is_active'] ?? true,
            employee_id: $data['employee_id'] ?? null,
            hourly_rate: $data['hourly_rate'] ?? null,
            permissions: $data['permissions'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            hire_date: $data['hire_date'] ?? null,
            password: $data['password'],
        );
    }

    /**
     * Create UserDTO from array data and existing User for updates
     */
    public function fromArrayWithExisting(array $data, User $existingUser): UserDTO
    {
        return new UserDTO(
            name: $data['name'] ?? $existingUser->name,
            email: $data['email'] ?? $existingUser->email,
            role: isset($data['role']) ? UserRole::from($data['role']) : $existingUser->role,
            is_active: $data['is_active'] ?? $existingUser->is_active,
            employee_id: $data['employee_id'] ?? $existingUser->employee_id,
            hourly_rate: $data['hourly_rate'] ?? $existingUser->hourly_rate,
            permissions: $data['permissions'] ?? $existingUser->permissions,
            phone: $data['phone'] ?? $existingUser->phone,
            address: $data['address'] ?? $existingUser->address,
            hire_date: $data['hire_date'] ?? $existingUser->hire_date?->format('Y-m-d'),
            password: $data['password'] ?? null,
        );
    }

    /**
     * Create UserDTO for employee creation with default employee settings
     */
    public function forEmployee(array $data): UserDTO
    {
        // Ensure default values appropriate for employees
        $employeeData = array_merge([
            'is_active' => true,
            'permissions' => null, // Will be set based on role
        ], $data);

        return $this->fromArray($employeeData);
    }

    /**
     * Create UserDTO for regular user creation
     */
    public function forUser(array $data): UserDTO
    {
        // Ensure default values appropriate for regular users
        $userData = array_merge([
            'is_active' => true,
            'employee_id' => null,
            'hourly_rate' => null,
            'permissions' => null,
        ], $data);

        return $this->fromArray($userData);
    }
}
