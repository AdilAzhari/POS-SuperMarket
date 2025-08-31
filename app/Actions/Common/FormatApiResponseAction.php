<?php

declare(strict_types=1);

namespace App\Actions\Common;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

final class FormatApiResponseAction
{
    /**
     * Format paginated data with consistent structure
     */
    public function paginated(LengthAwarePaginator $data, ?string $message = null): JsonResponse
    {
        $response = [
            'data' => $data->items(),
            'pagination' => [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
                'has_more' => $data->hasMorePages(),
            ],
        ];

        if ($message !== null && $message !== '' && $message !== '0') {
            $response['message'] = $message;
        }

        return response()->json($response);
    }

    /**
     * Format collection data with optional metadata
     */
    public function collection(mixed $data, array $meta = [], ?string $message = null): JsonResponse
    {
        $response = ['data' => $data];

        if ($message !== null && $message !== '' && $message !== '0') {
            $response['message'] = $message;
        }

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return response()->json($response);
    }

    /**
     * Format single resource data
     */
    public function resource(mixed $data, ?string $message = null): JsonResponse
    {
        $response = ['data' => $data];

        if ($message !== null && $message !== '' && $message !== '0') {
            $response['message'] = $message;
        }

        return response()->json($response);
    }

    /**
     * Format dashboard data with analytics
     */
    public function dashboard(array $data, array $analytics = [], array $meta = []): JsonResponse
    {
        $response = [
            'data' => $data,
            'generated_at' => now()->toISOString(),
        ];

        if ($analytics !== []) {
            $response['analytics'] = $analytics;
        }

        if ($meta !== []) {
            $response['meta'] = $meta;
        }

        return response()->json($response);
    }

    /**
     * Format report data with summary statistics
     */
    public function report(array $data, array $summary = [], array $filters = []): JsonResponse
    {
        $response = [
            'report_data' => $data,
            'generated_at' => now()->toISOString(),
        ];

        if ($summary !== []) {
            $response['summary'] = $summary;
        }

        if ($filters !== []) {
            $response['filters_applied'] = $filters;
        }

        return response()->json($response);
    }

    /**
     * Format success response with optional data
     */
    public function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        $response = ['message' => $message];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Format created response
     */
    public function created(mixed $data, string $message = 'Resource created successfully'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ], 201);
    }

    /**
     * Format updated response
     */
    public function updated(mixed $data, string $message = 'Resource updated successfully'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Format deleted response
     */
    public function deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return response()->json(['message' => $message]);
    }
}
