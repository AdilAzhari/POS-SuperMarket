<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
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
        // Get product ID from route parameter (can be string or model instance)
        $productId = $this->route('product');
        if (is_object($productId)) {
            $productId = $productId->id;
        }
        $productId = $productId ?? 'NULL';

        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'sku' => [
                'sometimes', 'string', 'max:255',
                "unique:products,sku,{$productId}",
            ],
            'barcode' => [
                'sometimes', 'string', 'max:255',
                "unique:products,barcode,{$productId}",
            ],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'cost' => ['sometimes', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'image_url' => ['nullable', 'url'],
        ];
    }
}
