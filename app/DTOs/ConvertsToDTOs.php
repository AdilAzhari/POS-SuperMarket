<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\CustomerStatus;
use App\Enums\LoyaltyTier;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Enums\UserRole;

trait ConvertsToDTOs
{
    public function toUserDTO(): UserDTO
    {
        $data = $this->validated();

        return new UserDTO(
            name: $data['name'],
            email: $data['email'],
            role: UserRole::from($data['role']),
            is_active: $data['is_active'] ?? true,
            employee_id: $data['employee_id'] ?? null,
            hourly_rate: $data['hourly_rate'] ?? null,
            permissions: $data['permissions'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            hire_date: $data['hire_date'] ?? null,
            password: $data['password'] ?? null,
        );
    }

    public function toCustomerDTO(): CustomerDTO
    {
        $data = $this->validated();

        return new CustomerDTO(
            name: $data['name'],
            phone: $data['phone'],
            email: $data['email'] ?? null,
            address: $data['address'] ?? null,
            status: isset($data['status']) ? CustomerStatus::from($data['status']) : CustomerStatus::ACTIVE,
            loyalty_points: $data['loyalty_points'] ?? 0,
            loyalty_tier: isset($data['loyalty_tier']) ? LoyaltyTier::from($data['loyalty_tier']) : LoyaltyTier::BRONZE,
            birthday: $data['birthday'] ?? null,
            marketing_consent: $data['marketing_consent'] ?? false,
        );
    }

    public function toProductDTO(): ProductDTO
    {
        $data = $this->validated();

        return new ProductDTO(
            name: $data['name'],
            sku: $data['sku'],
            price: (float) $data['price'],
            cost: (float) $data['cost'],
            category_id: $data['category_id'],
            supplier_id: $data['supplier_id'] ?? null,
            barcode: $data['barcode'] ?? null,
            internal_code: $data['internal_code'] ?? null,
            description: $data['description'] ?? null,
            active: $data['active'] ?? true,
            track_serial_numbers: $data['track_serial_numbers'] ?? false,
            track_batches: $data['track_batches'] ?? false,
            reorder_point: $data['reorder_point'] ?? 0,
            reorder_quantity: $data['reorder_quantity'] ?? 0,
            max_stock_level: $data['max_stock_level'] ?? null,
            weight: $data['weight'] ?? null,
            weight_unit: $data['weight_unit'] ?? 'kg',
            length: $data['length'] ?? null,
            width: $data['width'] ?? null,
            height: $data['height'] ?? null,
            dimension_unit: $data['dimension_unit'] ?? 'cm',
            is_featured: $data['is_featured'] ?? false,
            allow_backorder: $data['allow_backorder'] ?? false,
            discontinued_at: $data['discontinued_at'] ?? null,
            wholesale_price: $data['wholesale_price'] ?? null,
            compare_at_price: $data['compare_at_price'] ?? null,
            tax_exempt: $data['tax_exempt'] ?? false,
            manufacturer: $data['manufacturer'] ?? null,
            brand: $data['brand'] ?? null,
            notes: $data['notes'] ?? null,
            tags: $data['tags'] ?? null,
            image_url: $data['image_url'] ?? null,
        );
    }

    public function toStoreDTO(): StoreDTO
    {
        $data = $this->validated();

        return new StoreDTO(
            name: $data['name'],
            address: $data['address'],
            phone: $data['phone'],
            email: $data['email'] ?? null,
            manager_id: $data['manager_id'] ?? null,
            is_active: $data['is_active'] ?? true,
            operating_hours: $data['operating_hours'] ?? null,
            timezone: $data['timezone'] ?? null,
        );
    }

    public function toPaymentDTO(): PaymentDTO
    {
        $data = $this->validated();

        return new PaymentDTO(
            sale_id: $data['sale_id'],
            store_id: $data['store_id'],
            user_id: $data['user_id'],
            payment_method: PaymentMethod::from($data['payment_method']),
            amount: (float) $data['amount'],
            status: isset($data['status']) ? PaymentStatus::from($data['status']) : PaymentStatus::PENDING,
            payment_code: $data['payment_code'] ?? null,
            fee: (float) ($data['fee'] ?? 0),
            net_amount: $data['net_amount'] ?? null,
            currency: $data['currency'] ?? 'MYR',
            gateway_transaction_id: $data['gateway_transaction_id'] ?? null,
            gateway_reference: $data['gateway_reference'] ?? null,
            gateway_response: $data['gateway_response'] ?? null,
            card_last_four: $data['card_last_four'] ?? null,
            card_brand: $data['card_brand'] ?? null,
            card_exp_month: $data['card_exp_month'] ?? null,
            card_exp_year: $data['card_exp_year'] ?? null,
            tng_phone: $data['tng_phone'] ?? null,
            tng_reference: $data['tng_reference'] ?? null,
            cash_received: $data['cash_received'] ?? null,
            change_amount: $data['change_amount'] ?? null,
            notes: $data['notes'] ?? null,
            processed_at: $data['processed_at'] ?? null,
        );
    }

    public function toStockMovementDTO(): StockMovementDTO
    {
        $data = $this->validated();

        return new StockMovementDTO(
            product_id: $data['product_id'],
            store_id: $data['store_id'],
            user_id: $data['user_id'],
            type: StockMovementType::from($data['type']),
            quantity: $data['quantity'],
            reason: StockMovementReason::from($data['reason']),
            code: $data['code'] ?? null,
            notes: $data['notes'] ?? null,
            from_store_id: $data['from_store_id'] ?? null,
            to_store_id: $data['to_store_id'] ?? null,
            occurred_at: $data['occurred_at'] ?? null,
        );
    }

    public function toPurchaseOrderDTO(): PurchaseOrderDTO
    {
        $data = $this->validated();

        return new PurchaseOrderDTO(
            supplier_id: $data['supplier_id'],
            store_id: $data['store_id'],
            created_by: $data['created_by'],
            status: isset($data['status']) ? PurchaseOrderStatus::from($data['status']) : PurchaseOrderStatus::DRAFT,
            po_number: $data['po_number'] ?? null,
            total_amount: (float) ($data['total_amount'] ?? 0),
            notes: $data['notes'] ?? null,
            ordered_at: $data['ordered_at'] ?? null,
            expected_delivery_at: $data['expected_delivery_at'] ?? null,
            received_at: $data['received_at'] ?? null,
        );
    }
}
