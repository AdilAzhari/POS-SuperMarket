<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

final class InsufficientStockException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Insufficient Stock',
            'message' => $this->getMessage(),
        ], 422);
    }
}
