<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;

final class DTOFactory
{
    public static function fromUser(User $user): UserDTO
    {
        return new UserDTO(
            name: $user->name,
            email: $user->email,
            role: $user->role,
            is_active: $user->is_active,
            employee_id: $user->employee_id,
            hourly_rate: $user->hourly_rate,
            permissions: $user->permissions,
            phone: $user->phone,
            address: $user->address,
            hire_date: $user->hire_date?->format('Y-m-d'),
        );
    }

    public static function fromCustomer(Customer $customer): CustomerDTO
    {
        return new CustomerDTO(
            name: $customer->name,
            phone: $customer->phone,
            email: $customer->email,
            address: $customer->address,
            status: $customer->status,
            loyalty_points: $customer->loyalty_points,
            loyalty_tier: $customer->loyalty_tier,
            birthday: $customer->birthday?->format('Y-m-d'),
            marketing_consent: $customer->marketing_consent,
            total_purchases: $customer->total_purchases,
            total_spent: (float) $customer->total_spent,
            last_purchase_at: $customer->last_purchase_at?->toDateTimeString(),
        );
    }

    public static function fromProduct(Product $product): ProductDTO
    {
        return new ProductDTO(
            name: $product->name,
            sku: $product->sku,
            price: (float) $product->price,
            cost: (float) $product->cost,
            category_id: $product->category_id,
            supplier_id: $product->supplier_id,
            barcode: $product->barcode,
            internal_code: $product->internal_code,
            description: $product->description,
            active: $product->active,
            track_serial_numbers: $product->track_serial_numbers,
            track_batches: $product->track_batches,
            reorder_point: $product->reorder_point,
            reorder_quantity: $product->reorder_quantity,
            max_stock_level: $product->max_stock_level,
            weight: $product->weight,
            weight_unit: $product->weight_unit,
            length: $product->length,
            width: $product->width,
            height: $product->height,
            dimension_unit: $product->dimension_unit,
            is_featured: $product->is_featured,
            allow_backorder: $product->allow_backorder,
            discontinued_at: $product->discontinued_at?->format('Y-m-d'),
            wholesale_price: $product->wholesale_price,
            compare_at_price: $product->compare_at_price,
            tax_exempt: $product->tax_exempt,
            manufacturer: $product->manufacturer,
            brand: $product->brand,
            notes: $product->notes,
            tags: $product->tags,
            image_url: $product->image_url,
        );
    }

    public static function fromStore(Store $store): StoreDTO
    {
        return new StoreDTO(
            name: $store->name,
            address: $store->address,
            phone: $store->phone,
            email: $store->email,
            manager_id: $store->manager_id,
            is_active: $store->is_active,
            operating_hours: $store->operating_hours,
            timezone: $store->timezone,
        );
    }

    public static function fromSale(Sale $sale): SaleDTO
    {
        return new SaleDTO(
            store_id: $sale->store_id,
            cashier_id: $sale->cashier_id,
            payment_method: $sale->payment_method,
            status: $sale->status,
            customer_id: $sale->customer_id,
            code: $sale->code,
            items_count: $sale->items_count,
            subtotal: (float) $sale->subtotal,
            discount: (float) $sale->discount,
            tax: (float) $sale->tax,
            total: (float) $sale->total,
            paid_at: $sale->paid_at?->toDateTimeString(),
        );
    }

    public static function fromPayment(Payment $payment): PaymentDTO
    {
        return new PaymentDTO(
            sale_id: $payment->sale_id,
            store_id: $payment->store_id,
            user_id: $payment->user_id,
            payment_method: $payment->payment_method,
            amount: (float) $payment->amount,
            status: $payment->status,
            payment_code: $payment->payment_code,
            fee: (float) $payment->fee,
            net_amount: (float) $payment->net_amount,
            currency: $payment->currency,
            gateway_transaction_id: $payment->gateway_transaction_id,
            gateway_reference: $payment->gateway_reference,
            gateway_response: $payment->gateway_response,
            card_last_four: $payment->card_last_four,
            card_brand: $payment->card_brand,
            card_exp_month: $payment->card_exp_month,
            card_exp_year: $payment->card_exp_year,
            tng_phone: $payment->tng_phone,
            tng_reference: $payment->tng_reference,
            cash_received: $payment->cash_received,
            change_amount: $payment->change_amount,
            notes: $payment->notes,
            processed_at: $payment->processed_at?->toDateTimeString(),
        );
    }

    public static function fromStockMovement(StockMovement $movement): StockMovementDTO
    {
        return new StockMovementDTO(
            product_id: $movement->product_id,
            store_id: $movement->store_id,
            user_id: $movement->user_id,
            type: $movement->type,
            quantity: $movement->quantity,
            reason: $movement->reason,
            code: $movement->code,
            notes: $movement->notes,
            from_store_id: $movement->from_store_id,
            to_store_id: $movement->to_store_id,
            occurred_at: $movement->occurred_at?->toDateTimeString(),
        );
    }

    public static function fromPurchaseOrder(PurchaseOrder $order): PurchaseOrderDTO
    {
        return new PurchaseOrderDTO(
            supplier_id: $order->supplier_id,
            store_id: $order->store_id,
            created_by: $order->created_by,
            status: $order->status,
            po_number: $order->po_number,
            total_amount: (float) $order->total_amount,
            notes: $order->notes,
            ordered_at: $order->ordered_at?->toDateTimeString(),
            expected_delivery_at: $order->expected_delivery_at?->toDateTimeString(),
            received_at: $order->received_at?->toDateTimeString(),
        );
    }
}
