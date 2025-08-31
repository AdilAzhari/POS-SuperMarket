<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

final class SaleProcessingException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'error' => 'Sale Processing Failed',
            'message' => $this->getMessage(),
        ], 500);
    }
}
