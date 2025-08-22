<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to manage employees
        return auth()->user()?->hasPermission('manage_employees') || auth()->user()?->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isUpdating = $this->getMethod() === 'PUT' || $this->getMethod() === 'PATCH';
        $employeeId = $this->route('employee')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($employeeId),
            ],
            'password' => [
                $isUpdating ? 'nullable' : 'required',
                'string',
                'min:8',
            ],
            'role' => ['required', Rule::in(['admin', 'manager', 'supervisor', 'cashier'])],
            'employee_id' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'employee_id')->ignore($employeeId),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'hire_date' => ['nullable', 'date', 'before_or_equal:today'],
            'is_active' => ['boolean'],
            'permissions' => ['nullable', 'array'],
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
