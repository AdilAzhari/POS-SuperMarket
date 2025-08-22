<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatestoreRequest extends FormRequest
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
        $storeId = $this->route('store');
        if (is_object($storeId)) {
            $storeId = $storeId->id;
        }
        $storeId = $storeId ?? 'NULL';

        return [
            'name' => ['sometimes', 'string', 'max:255', "unique:stores,name,{$storeId}"],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[\+]?[0-9\s\-\(\)]+$/'],
            'email' => ['nullable', 'email', 'max:255', "unique:stores,email,{$storeId}"],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A store with this name already exists.',
            'phone.regex' => 'Please enter a valid phone number format.',
            'email.unique' => 'A store with this email already exists.',
        ];
    }
}
