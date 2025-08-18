<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientStockException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Insufficient Stock',
            'message' => $this->getMessage(),
        ], 422);
    }
}