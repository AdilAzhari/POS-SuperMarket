<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ConvertsToDTOs;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreCategoryRequest extends FormRequest
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
        $categoryId = $this->route('category')?->id ?? $this->route('id');

        // For updates, make most fields optional with 'sometimes'
        $requiredOrSometimes = $isUpdating ? 'sometimes' : 'required';

        return [
            'name' => [
                $requiredOrSometimes,
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($categoryId),
            ],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->ignore($categoryId),
            ],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'This category name is already taken.',
            'slug.unique' => 'This category slug is already taken.',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'slug' => 'category slug',
        ];
    }
}
