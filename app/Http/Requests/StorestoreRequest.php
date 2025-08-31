<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorestoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:stores,name'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[\+]?[0-9\s\-\(\)]+$/'],
            'email' => ['nullable', 'email', 'max:255', 'unique:stores,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Store name is required.',
            'name.unique' => 'A store with this name already exists.',
            'phone.regex' => 'Please enter a valid phone number format.',
            'email.unique' => 'A store with this email already exists.',
        ];
    }
}
