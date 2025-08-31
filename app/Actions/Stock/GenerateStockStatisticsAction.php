<?php

declare(strict_types=1);

namespace App\Actions\Stock;

use App\Models\StockMovement;
use Carbon\Carbon;

final class GenerateStockStatisticsAction
{
    /**
     * Generate comprehensive stock movement statistics
     */
    public function execute(?string $dateFrom, ?string $dateTo, ?int $storeId): array
    {
        $query = StockMovement::query()->with(['product']);

        if ($dateFrom !== null && $dateFrom !== '' && $dateFrom !== '0') {
            $query->whereDate('occurred_at', '>=', $dateFrom);
        }
        if ($dateTo !== null && $dateTo !== '' && $dateTo !== '0') {
            $query->whereDate('occurred_at', '<=', $dateTo);
        }
        if ($storeId !== null && $storeId !== 0) {
            $query->where('store_id', $storeId);
        }

        $movements = $query->get();

        return [
            'total_movements' => $movements->count(),
            'by_type' => $this->getMovementsByType($movements),
            'by_reason' => $this->getMovementsByReason($movements),
            'daily_summary' => $this->getDailySummary($movements),
            'top_products' => $this->getTopProducts($movements),
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
                'store_id' => $storeId,
            ],
        ];
    }

    private function getMovementsByType($movements): array
    {
        return [
            'addition' => $movements->where('type', \App\Enums\StockMovementType::ADDITION)->sum('quantity'),
            'reduction' => $movements->where('type', \App\Enums\StockMovementType::REDUCTION)->sum('quantity'),
            'transfer_in' => $movements->where('type', \App\Enums\StockMovementType::TRANSFER_IN)->count(),
            'transfer_out' => $movements->where('type', \App\Enums\StockMovementType::TRANSFER_OUT)->count(),
            'adjustment' => $movements->where('type', \App\Enums\StockMovementType::ADJUSTMENT)->count(),
        ];
    }

    private function getMovementsByReason($movements): array
    {
        return $movements->groupBy('reason')->map(fn ($group): array => [
            'count' => $group->count(),
            'total_quantity' => $group->sum('quantity'),
        ])->toArray();
    }

    private function getDailySummary($movements): array
    {
        return $movements->groupBy(function ($movement) {
            $date = $movement->occurred_at?->format('Y-m-d');
            if (! $date && isset($movement->created_at)) {
                $date = Carbon::parse($movement->created_at)->format('Y-m-d');
            }

            return $date ?: now()->format('Y-m-d');
        })->map(fn ($dayMovements): array => [
            'total_movements' => $dayMovements->count(),
            'additions' => $dayMovements->where('type', \App\Enums\StockMovementType::ADDITION)->sum('quantity'),
            'reductions' => $dayMovements->where('type', \App\Enums\StockMovementType::REDUCTION)->sum('quantity'),
            'transfers' => $dayMovements->whereIn('type', [
                \App\Enums\StockMovementType::TRANSFER_IN,
                \App\Enums\StockMovementType::TRANSFER_OUT,
            ])->count(),
        ])->sortKeys()->toArray();
    }

    private function getTopProducts($movements): array
    {
        return $movements->groupBy('product_id')
            ->map(fn ($productMovements): array => [
                'product_id' => $productMovements->first()->product_id,
                'product_name' => $productMovements->first()?->product?->name ?? 'Unknown',
                'movement_count' => $productMovements->count(),
                'total_quantity_moved' => $productMovements->sum('quantity'),
            ])
            ->sortByDesc('movement_count')
            ->take(10)
            ->values()
            ->toArray();
    }
}
