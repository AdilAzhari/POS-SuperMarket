<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ConvertsToDTOs;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCustomerRequest extends FormRequest
{
    use ConvertsToDTOs;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $isUpdating = $this->getMethod() === 'PUT' || $this->getMethod() === 'PATCH';
        $customerId = $this->route('customer')?->id ?? $this->route('id');

        // For updates, make most fields optional with 'sometimes'
        $requiredOrSometimes = $isUpdating ? 'sometimes' : 'required';

        return [
            'name' => [$requiredOrSometimes, 'string', 'max:255'],
            'phone' => [
                $requiredOrSometimes,
                'string',
                'max:255',
                Rule::unique('customers', 'phone')->ignore($customerId),
            ],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customerId),
            ],
            'address' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'phone.unique' => 'This phone number is already taken by another customer.',
            'email.unique' => 'This email address is already taken by another customer.',
            'status.in' => 'Status must be either active or inactive.',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'phone' => 'phone number',
            'email' => 'email address',
        ];
    }
}
