<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ConvertsToDTOs;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreEmployeeRequest extends FormRequest
{
    use ConvertsToDTOs;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to manage employees
        if (auth()->user()?->hasPermission('manage_employees')) {
            return true;
        }

        return (bool) auth()->user()?->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $isUpdating = $this->getMethod() === 'PUT' || $this->getMethod() === 'PATCH';
        $employeeId = $this->route('employee')?->id ?? $this->route('user')?->id;

        // For updates, make most fields optional with 'sometimes'
        $requiredOrSometimes = $isUpdating ? 'sometimes' : 'required';

        return [
            'name' => [$requiredOrSometimes, 'string', 'max:255'],
            'email' => [
                $requiredOrSometimes,
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employeeId),
            ],
            'password' => [
                $isUpdating ? 'sometimes' : 'required',
                'string',
                'min:8',
            ],
            'role' => [$requiredOrSometimes, Rule::in(UserRole::getValues())],
            'employee_id' => [
                'sometimes', // Always optional
                'string',
                'max:50',
                Rule::unique('users', 'employee_id')->ignore($employeeId),
            ],
            'phone' => ['sometimes', 'string', 'max:20'],
            'address' => ['sometimes', 'string', 'max:500'],
            'hourly_rate' => ['sometimes', 'numeric', 'min:0', 'max:999.99'],
            'hire_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'is_active' => ['sometimes', 'boolean'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string'],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already taken by another employee.',
            'employee_id.unique' => 'This employee ID is already in use.',
            'role.in' => 'The selected role is invalid.',
            'hourly_rate.numeric' => 'Hourly rate must be a valid number.',
            'hire_date.before_or_equal' => 'Hire date cannot be in the future.',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'employee ID',
            'hourly_rate' => 'hourly rate',
            'hire_date' => 'hire date',
            'is_active' => 'status',
        ];
    }
}
