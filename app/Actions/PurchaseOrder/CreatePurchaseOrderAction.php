<?php

declare(strict_types=1);

namespace App\Actions\PurchaseOrder;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreatePurchaseOrderAction
{
    /**
     * Create a new purchase order with items
     */
    public function execute(array $data, int $userId): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            // Calculate totals
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += $item['quantity_ordered'] * $item['unit_cost'];
            }

            $tax = $subtotal * 0.1; // Assuming 10% tax
            $total = $subtotal + $tax;

            // Create purchase order
            $purchaseOrder = PurchaseOrder::create([
                'po_number' => $this->generatePoNumber(),
                'supplier_id' => $data['supplier_id'],
                'store_id' => $data['store_id'],
                'status' => 'pending',
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'notes' => $data['notes'] ?? null,
                'expected_delivery_at' => $data['expected_delivery_at'] ?? null,
                'created_by' => $userId,
            ]);

            // Create purchase order items
            foreach ($data['items'] as $itemData) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_id' => $itemData['product_id'],
                    'quantity_ordered' => $itemData['quantity_ordered'],
                    'unit_cost' => $itemData['unit_cost'],
                    'total_cost' => $itemData['quantity_ordered'] * $itemData['unit_cost'],
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            // Load relationships for response
            $purchaseOrder->load(['supplier', 'store', 'createdBy', 'items.product']);

            return $purchaseOrder;
        });
    }

    private function generatePoNumber(): string
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        $random = Str::upper(Str::random(4));

        return "{$prefix}-{$date}-{$random}";
    }
}
