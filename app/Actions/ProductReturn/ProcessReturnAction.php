<?php

declare(strict_types=1);

namespace App\Actions\ProductReturn;

use App\DTOs\ProductReturnDTO;
use App\DTOs\ReturnItemDTO;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final class ProcessReturnAction
{
    /**
     * Process product return with inventory restoration and payment refund
     *
     * @param  array<int, array{sale_item_id: int, quantity: int, condition_notes?: string}>  $returnItems
     *
     * @throws Throwable
     */
    public function execute(ProductReturnDTO $returnDTO, array $returnItems): ProductReturn
    {
        return DB::transaction(function () use ($returnDTO, $returnItems) {
            // Create the return record
            $return = ProductReturn::create($returnDTO->toCreateArray());

            $totalRefund = 0;
            $totalTaxRefund = 0;

            // Process each returned item
            foreach ($returnItems as $itemData) {
                $saleItem = SaleItem::findOrFail($itemData['sale_item_id']);
                $quantityReturned = $itemData['quantity'];

                // Validate quantity
                if ($quantityReturned > $saleItem->quantity) {
                    throw new InvalidArgumentException(
                        "Cannot return {$quantityReturned} units of {$saleItem->product_name}. Only {$saleItem->quantity} were purchased."
                    );
                }

                // Calculate line total for this return
                $lineTotal = $saleItem->price * $quantityReturned;
                $lineTax = ($saleItem->tax / $saleItem->quantity) * $quantityReturned;

                // Create return item record
                $returnItemDTO = new ReturnItemDTO(
                    return_id: $return->id,
                    sale_item_id: $saleItem->id,
                    product_id: $saleItem->product_id,
                    product_name: $saleItem->product_name,
                    sku: $saleItem->sku,
                    price: (float) $saleItem->price,
                    quantity_returned: $quantityReturned,
                    original_quantity: $saleItem->quantity,
                    line_total: $lineTotal,
                    condition_notes: $itemData['condition_notes'] ?? null,
                );

                ReturnItem::create($returnItemDTO->toCreateArray());

                // Restore inventory
                $this->restoreInventory(
                    productId: $saleItem->product_id,
                    storeId: $return->store_id,
                    quantity: $quantityReturned,
                    returnCode: $return->code,
                    userId: $return->processed_by
                );

                $totalRefund += $lineTotal;
                $totalTaxRefund += $lineTax;
            }

            // Update return totals
            $return->update([
                'subtotal' => $totalRefund,
                'tax_refund' => $totalTaxRefund,
                'total_refund' => $totalRefund + $totalTaxRefund,
            ]);

            // Process refund payment
            $this->processRefundPayment($return);

            // Update sale status
            $this->updateSaleStatus($return->sale);

            Log::info('[ProductReturn] Processed return successfully', [
                'return_id' => $return->id,
                'return_code' => $return->code,
                'total_refund' => $return->total_refund,
                'items_count' => count($returnItems),
            ]);

            return $return->load(['items', 'sale', 'customer', 'processedBy']);
        });
    }

    /**
     * Restore inventory by creating a stock movement
     */
    private function restoreInventory(int $productId, int $storeId, int $quantity, string $returnCode, int $userId): void
    {
        $product = Product::findOrFail($productId);
        $store = Store::findOrFail($storeId);

        // Create stock movement for the return
        StockMovement::create([
            'code' => 'STK-RET-'.date('ymdHis').'-'.random_int(100, 999),
            'product_id' => $productId,
            'store_id' => $storeId,
            'user_id' => $userId,
            'type' => 'addition',
            'quantity' => $quantity,
            'reason' => 'return',
            'notes' => "Product return {$returnCode}",
            'occurred_at' => now(),
        ]);

        // Update product stock in pivot table
        $pivot = $product->stores()->where('stores.id', $storeId)->first();
        if (! $pivot) {
            $product->stores()->attach($storeId, ['stock' => $quantity, 'low_stock_threshold' => 0]);
        } else {
            $currentStock = $pivot->pivot->stock ?? 0;
            $product->stores()->updateExistingPivot($storeId, [
                'stock' => $currentStock + $quantity,
            ]);
        }

        Log::info('[ProductReturn] Restored inventory', [
            'product_id' => $productId,
            'store_id' => $storeId,
            'quantity' => $quantity,
            'return_code' => $returnCode,
        ]);
    }

    /**
     * Process refund payment
     */
    private function processRefundPayment(ProductReturn $return): void
    {
        // Create a negative payment (refund) record
        $refundPayment = Payment::create([
            'payment_code' => 'REF-'.date('ymdHis').'-'.random_int(100, 999),
            'sale_id' => $return->sale_id,
            'store_id' => $return->store_id,
            'user_id' => $return->processed_by,
            'payment_method' => $this->determineRefundMethod($return),
            'status' => PaymentStatus::COMPLETED,
            'amount' => -$return->total_refund, // Negative amount for refund
            'fee' => 0,
            'net_amount' => -$return->total_refund,
            'currency' => 'MYR',
            'notes' => "Refund for return {$return->code}",
            'processed_at' => now(),
        ]);

        Log::info('[ProductReturn] Processed refund payment', [
            'payment_id' => $refundPayment->id,
            'payment_code' => $refundPayment->payment_code,
            'amount' => $refundPayment->amount,
            'return_code' => $return->code,
        ]);
    }

    /**
     * Determine payment method for refund
     */
    private function determineRefundMethod(ProductReturn $return): PaymentMethod
    {
        return match ($return->refund_method) {
            'original_payment' => $return->sale->payment_method,
            'cash' => PaymentMethod::CASH,
            'store_credit', 'exchange' => PaymentMethod::CASH, // Default to cash for now
            default => PaymentMethod::CASH,
        };
    }

    /**
     * Update sale status based on returned items
     */
    private function updateSaleStatus(Sale $sale): void
    {
        $sale->load('items');

        // Calculate total returned quantity
        $returnedQuantity = ReturnItem::query()
            ->whereHas('productReturn', fn ($q) => $q->where('sale_id', $sale->id))
            ->sum('quantity_returned');

        // Calculate total original quantity
        $totalQuantity = $sale->items->sum('quantity');

        // Update sale status
        if ($returnedQuantity >= $totalQuantity) {
            $sale->update(['status' => SaleStatus::REFUNDED]);
        } elseif ($returnedQuantity > 0) {
            $sale->update(['status' => SaleStatus::PARTIALLY_REFUNDED]);
        }

        Log::info('[ProductReturn] Updated sale status', [
            'sale_id' => $sale->id,
            'status' => $sale->status->value,
            'returned_quantity' => $returnedQuantity,
            'total_quantity' => $totalQuantity,
        ]);
    }
}
