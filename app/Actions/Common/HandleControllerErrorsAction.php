<?php

declare(strict_types=1);

namespace App\Actions\Common;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class HandleControllerErrorsAction
{
    /**
     * Handle controller errors with consistent response format
     */
    public function execute(Exception $e, string $context = 'operation'): JsonResponse
    {
        // Log the error
        Log::error("[Controller] Error in $context", [
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => auth()->id(),
        ]);

        // Handle specific exception types
        return match (true) {
            $e instanceof ValidationException => $this->handleValidationError($e),
            $e instanceof HttpException => $this->handleHttpError($e),
            default => $this->handleGenericError($context),
        };
    }

    /**
     * Handle successful operations with consistent format
     */
    public function success(mixed $data = null, string $message = 'Operation completed successfully', int $status = 200): JsonResponse
    {
        $response = ['message' => $message];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Handle creation success with consistent format
     */
    public function created(mixed $data, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    /**
     * Handle validation errors with field-specific messages
     */
    private function handleValidationError(ValidationException $e): JsonResponse
    {
        return response()->json([
            'error' => 'Validation Failed',
            'message' => 'The provided data is invalid',
            'errors' => $e->errors(),
        ], 422);
    }

    /**
     * Handle HTTP exceptions with appropriate status codes
     */
    private function handleHttpError(HttpException $e): JsonResponse
    {
        return response()->json([
            'error' => $this->getErrorTitle($e->getStatusCode()),
            'message' => in_array($e->getMessage(), ['', '0'], true) ? $this->getDefaultMessage($e->getStatusCode()) : $e->getMessage(),
        ], $e->getStatusCode());
    }

    /**
     * Handle generic exceptions with safe error messages
     */
    private function handleGenericError(string $context): JsonResponse
    {
        return response()->json([
            'error' => 'Operation Failed',
            'message' => "Failed to complete $context. Please try again.",
        ], 500);
    }

    /**
     * Get error title based on status code
     */
    private function getErrorTitle(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'Error',
        };
    }

    /**
     * Get default message based on status code
     */
    private function getDefaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'The request was invalid',
            401 => 'Authentication is required',
            403 => 'You do not have permission to perform this action',
            404 => 'The requested resource was not found',
            409 => 'The request conflicts with the current state',
            422 => 'The provided data is invalid',
            429 => 'Too many requests. Please try again later',
            500 => 'An internal server error occurred',
            503 => 'The service is temporarily unavailable',
            default => 'An error occurred',
        };
    }
}
