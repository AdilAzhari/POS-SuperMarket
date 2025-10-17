<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

final class StoreProductReturnRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sale_id' => ['required', 'exists:sales,id'],
            'reason' => [
                'required',
                'string',
                'in:defective,wrong_item,customer_change_mind,damaged_shipping,not_as_described,duplicate_order,other',
            ],
            'refund_method' => [
                'required',
                'string',
                'in:original_payment,cash,store_credit,exchange',
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'processed_by' => ['nullable', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.condition_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'sale_id.required' => 'Sale ID is required.',
            'sale_id.exists' => 'The selected sale does not exist.',
            'reason.required' => 'Return reason is required.',
            'reason.in' => 'Invalid return reason selected.',
            'refund_method.required' => 'Refund method is required.',
            'refund_method.in' => 'Invalid refund method selected.',
            'items.required' => 'At least one item must be returned.',
            'items.min' => 'At least one item must be returned.',
            'items.*.sale_item_id.required' => 'Sale item ID is required for each item.',
            'items.*.sale_item_id.exists' => 'The selected sale item does not exist.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();

        Log::warning('[StoreProductReturnRequest] Validation failed', [
            'request_data' => $this->all(),
            'validation_errors' => $errors,
            'user_agent' => $this->userAgent(),
            'ip' => $this->ip(),
        ]);

        throw new HttpResponseException(
            response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422)
        );
    }
}
