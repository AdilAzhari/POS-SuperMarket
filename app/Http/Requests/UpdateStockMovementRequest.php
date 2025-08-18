<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockMovementRequest extends FormRequest
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
            'type' => ['sometimes', 'in:addition,reduction,transfer_out,transfer_in'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'reason' => ['sometimes', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'from_store_id' => ['nullable', 'exists:stores,id'],
            'to_store_id' => ['nullable', 'exists:stores,id'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
