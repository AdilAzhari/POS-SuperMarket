<?php

declare(strict_types=1);

namespace App\Actions\Reorder;

use App\Services\ReorderService;

final readonly class GenerateReorderListAction
{
    public function __construct(
        private ReorderService $reorderService
    ) {}

    /**
     * Generate comprehensive reorder list with analysis
     */
    public function execute(int $storeId, string $groupBy = 'product'): array
    {
        $reorderList = match ($groupBy) {
            'supplier' => $this->reorderService->getReorderListBySupplier($storeId),
            default => $this->reorderService->getReorderList($storeId),
        };

        return [
            'items' => $reorderList,
            'analytics' => $this->generateAnalytics($reorderList),
            'recommendations' => $this->generateRecommendations($reorderList),
            'meta' => [
                'store_id' => $storeId,
                'group_by' => $groupBy,
                'generated_at' => now(),
                'total_items' => $reorderList->count(),
                'high_priority' => $reorderList->where('priority', '>=', 4)->count(),
                'total_estimated_cost' => $reorderList->sum('estimated_cost'),
            ],
        ];
    }

    private function generateAnalytics(\Illuminate\Support\Collection $reorderList): array
    {
        return [
            'by_priority' => [
                'urgent' => $reorderList->where('priority', 5)->count(),
                'high' => $reorderList->where('priority', 4)->count(),
                'medium' => $reorderList->where('priority', 3)->count(),
                'low' => $reorderList->where('priority', '<=', 2)->count(),
            ],
            'by_supplier' => $reorderList->groupBy('supplier_id')->map(fn ($items): array => [
                'count' => $items->count(),
                'estimated_cost' => $items->sum('estimated_cost'),
                'supplier_name' => $items->first()['supplier_name'] ?? 'Unknown',
            ])->toArray(),
            'cost_distribution' => [
                'under_100' => $reorderList->where('estimated_cost', '<', 100)->count(),
                '100_to_500' => $reorderList->whereBetween('estimated_cost', [100, 500])->count(),
                '500_to_1000' => $reorderList->whereBetween('estimated_cost', [500, 1000])->count(),
                'over_1000' => $reorderList->where('estimated_cost', '>', 1000)->count(),
            ],
        ];
    }

    private function generateRecommendations(\Illuminate\Support\Collection $reorderList): array
    {
        $recommendations = [];

        // High priority items
        $urgentItems = $reorderList->where('priority', '>=', 4);
        if ($urgentItems->count() > 0) {
            $recommendations[] = [
                'type' => 'urgent_reorder',
                'message' => "You have {$urgentItems->count()} high-priority items that need immediate reordering",
                'action' => 'Create purchase orders for urgent items',
                'items_count' => $urgentItems->count(),
                'estimated_cost' => $urgentItems->sum('estimated_cost'),
            ];
        }

        // Bulk supplier orders
        $supplierGroups = $reorderList->groupBy('supplier_id');
        $bulkOpportunities = $supplierGroups->filter(fn ($items): bool => $items->count() >= 3 && $items->sum('estimated_cost') >= 500);

        if ($bulkOpportunities->count() > 0) {
            $recommendations[] = [
                'type' => 'bulk_order',
                'message' => "Consider bulk orders from {$bulkOpportunities->count()} suppliers for better pricing",
                'action' => 'Group orders by supplier',
                'suppliers' => $bulkOpportunities->keys()->toArray(),
            ];
        }

        // Seasonal considerations
        $highValueItems = $reorderList->where('estimated_cost', '>', 1000);
        if ($highValueItems->count() > 0) {
            $recommendations[] = [
                'type' => 'high_value_review',
                'message' => "Review {$highValueItems->count()} high-value items for seasonal demand patterns",
                'action' => 'Analyze demand trends',
                'items_count' => $highValueItems->count(),
            ];
        }

        return $recommendations;
    }
}
