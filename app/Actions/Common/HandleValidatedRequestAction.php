<?php

declare(strict_types=1);

namespace App\Actions\Common;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

final class HandleValidatedRequestAction
{
    /**
     * Extract and process validated data from request
     */
    public function execute(Request $request, array $rules = [], array $messages = []): array
    {
        if ($request instanceof FormRequest) {
            // Form request with predefined validation
            return $request->validated();
        }

        // Manual validation for regular requests
        if ($rules !== []) {
            return $request->validate($rules, $messages);
        }

        // Return all request data if no validation specified
        return $request->all();
    }

    /**
     * Extract validated data and convert to DTO-friendly format
     */
    public function extractForDTO(Request $request, array $defaults = []): array
    {
        $validated = $this->execute($request);

        // Merge with defaults, validated data takes precedence
        return array_merge($defaults, $validated);
    }

    /**
     * Handle common validation patterns
     */
    public function validateCommonFields(Request $request, array $extraRules = []): array
    {
        $baseRules = [
            'store_id' => 'sometimes|exists:stores,id',
            'user_id' => 'sometimes|exists:users,id',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255',
        ];

        return $this->execute($request, array_merge($baseRules, $extraRules));
    }

    /**
     * Handle pagination parameters validation
     */
    public function validatePagination(Request $request, int $defaultPerPage = 20, int $maxPerPage = 100): array
    {
        return $this->execute($request, [
            'per_page' => "sometimes|integer|min:1|max:$maxPerPage",
            'page' => 'sometimes|integer|min:1',
        ]);
    }

    /**
     * Handle date range validation
     */
    public function validateDateRange(Request $request, bool $required = false): array
    {
        $prefix = $required ? 'required' : 'sometimes';

        return $this->execute($request, [
            'date_from' => "$prefix|date",
            'date_to' => "$prefix|date|after_or_equal:date_from",
        ]);
    }

    /**
     * Handle store context validation
     */
    public function validateStoreContext(Request $request, bool $required = false): array
    {
        $rule = $required ? 'required|exists:stores,id' : 'sometimes|exists:stores,id';

        return $this->execute($request, [
            'store_id' => $rule,
        ]);
    }
}
