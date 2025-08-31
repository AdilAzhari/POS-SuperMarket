<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\ConvertsToDTOs;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProductRequest extends FormRequest
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
        $productId = $this->route('product')?->id ?? $this->route('id');

        // For updates, make most fields optional with 'sometimes'
        $requiredOrSometimes = $isUpdating ? 'sometimes' : 'required';

        return [
            'category_id' => ['sometimes', 'exists:categories,id'],
            'supplier_id' => ['sometimes', 'exists:suppliers,id'],
            'name' => [$requiredOrSometimes, 'string', 'max:255'],
            'sku' => [
                $requiredOrSometimes,
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'barcode' => [
                $requiredOrSometimes,
                'string',
                'max:255',
                Rule::unique('products', 'barcode')->ignore($productId),
            ],
            'price' => [$requiredOrSometimes, 'numeric', 'min:0'],
            'cost' => ['sometimes', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'image_url' => ['sometimes', 'url'],
        ];
    }

    /**
     * Get custom validation messages
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU is already taken by another product.',
            'barcode.unique' => 'This barcode is already taken by another product.',
            'price.numeric' => 'Price must be a valid number.',
            'cost.numeric' => 'Cost must be a valid number.',
            'category_id.exists' => 'The selected category does not exist.',
            'supplier_id.exists' => 'The selected supplier does not exist.',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'supplier_id' => 'supplier',
            'low_stock_threshold' => 'low stock threshold',
            'image_url' => 'image URL',
        ];
    }
}
