<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ConvertsToDTOs;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSupplierRequest extends FormRequest
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
        $supplierId = $this->route('supplier')?->id ?? $this->route('id');

        // For updates, make most fields optional with 'sometimes'
        $requiredOrSometimes = $isUpdating ? 'sometimes' : 'required';

        return [
            'name' => [
                $requiredOrSometimes,
                'string',
                'max:255',
                Rule::unique('suppliers', 'name')->ignore($supplierId),
            ],
            'contact_phone' => ['sometimes', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'email', 'max:255'],
            'address' => ['sometimes', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This supplier name is already taken.',
            'contact_email.email' => 'Please provide a valid email address.',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'contact_phone' => 'contact phone number',
            'contact_email' => 'contact email address',
        ];
    }
}
