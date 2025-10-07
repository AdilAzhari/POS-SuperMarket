<?php

declare(strict_types=1);

namespace App\Actions\Reorder;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreatePurchaseOrderFromReorderAction
{
    /**
     * Create purchase order from reorder items with intelligent grouping
     */
    public function execute(array $items, int $storeId, int $userId, ?string $notes = null): array
    {
        // Group items by supplier for separate purchase orders
        $itemsBySupplier = collect($items)->groupBy('supplier_id');
        $purchaseOrders = [];

        DB::transaction(function () use ($itemsBySupplier, $storeId, $userId, $notes, &$purchaseOrders): void {
            foreach ($itemsBySupplier as $supplierId => $supplierItems) {
                $purchaseOrder = $this->createPurchaseOrderForSupplier(
                    $supplierItems->toArray(),
                    (int) $supplierId,
                    $storeId,
                    $userId,
                    $notes
                );

                $purchaseOrders[] = $purchaseOrder;
            }
        });

        return [
            'purchase_orders' => $purchaseOrders,
            'summary' => $this->generateOrderSummary($purchaseOrders),
            'recommendations' => $this->generatePostOrderRecommendations($purchaseOrders),
        ];
    }

    private function createPurchaseOrderForSupplier(
        array $items,
        int $supplierId,
        int $storeId,
        int $userId,
        ?string $notes
    ): PurchaseOrder {
        // Calculate totals
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_cost'];
        }

        $tax = $subtotal * 0.1; // Assuming 10% tax
        $total = $subtotal + $tax;

        // Create purchase order
        $purchaseOrder = PurchaseOrder::create([
            'po_number' => $this->generatePoNumber(),
            'supplier_id' => $supplierId,
            'store_id' => $storeId,
            'status' => 'pending',
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'notes' => $notes,
            'created_by' => $userId,
            'expected_delivery_at' => now()->addDays(7), // Default 7 days
        ]);

        // Create purchase order items
        foreach ($items as $item) {
            $notes = $item['notes'] ?? null;
            if (! $notes && isset($item['priority'])) {
                $notes = "Reorder - Priority: {$item['priority']}";
            }

            PurchaseOrderItem::create([
                'purchase_order_id' => $purchaseOrder->id,
                'product_id' => $item['product_id'],
                'quantity_ordered' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'total_cost' => $item['quantity'] * $item['unit_cost'],
                'notes' => $notes,
            ]);
        }

        return $purchaseOrder->load(['supplier', 'store', 'createdBy', 'items.product']);
    }

    private function generatePoNumber(): string
    {
        $prefix = 'PO-RO'; // PO-Reorder
        $date = now()->format('Ymd');
        $random = Str::upper(Str::random(4));

        return "{$prefix}-{$date}-{$random}";
    }

    private function generateOrderSummary(array $purchaseOrders): array
    {
        $totalOrders = count($purchaseOrders);
        $totalAmount = 0;
        $totalItems = 0;
        $supplierIds = [];

        foreach ($purchaseOrders as $po) {
            $totalAmount += (float) $po->total_amount;
            $totalItems += $po->items->count();
            $supplierIds[] = $po->supplier_id;
        }

        return [
            'total_purchase_orders' => $totalOrders,
            'total_amount' => $totalAmount,
            'total_items' => $totalItems,
            'suppliers_involved' => array_unique($supplierIds),
            'estimated_delivery_window' => [
                'earliest' => now()->addDays(3)->format('Y-m-d'),
                'latest' => now()->addDays(14)->format('Y-m-d'),
            ],
        ];
    }

    private function generatePostOrderRecommendations(array $purchaseOrders): array
    {
        $recommendations = [];

        // Delivery scheduling
        if (count($purchaseOrders) > 1) {
            $recommendations[] = [
                'type' => 'delivery_coordination',
                'message' => 'Consider coordinating delivery schedules with multiple suppliers',
                'action' => 'Contact suppliers to align delivery dates',
                'priority' => 'medium',
            ];
        }

        // Inventory space planning
        $totalItems = array_sum(array_map(fn ($po) => $po->items->count(), $purchaseOrders));
        if ($totalItems > 50) {
            $recommendations[] = [
                'type' => 'storage_planning',
                'message' => "Large order incoming ({$totalItems} items) - ensure adequate storage space",
                'action' => 'Review warehouse capacity',
                'priority' => 'high',
            ];
        }

        // Follow-up tracking
        $recommendations[] = [
            'type' => 'order_tracking',
            'message' => 'Set up tracking notifications for all purchase orders',
            'action' => 'Enable automated delivery notifications',
            'priority' => 'low',
        ];

        return $recommendations;
    }
}
