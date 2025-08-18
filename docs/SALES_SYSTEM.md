# Sales System

## Overview
Complete point-of-sale system with transaction processing, payment handling, and sales tracking.

## Features
- POS interface for quick sales
- Multiple payment methods
- Transaction processing
- Receipt generation
- Sales history tracking
- Refund processing
- Discount applications

## Controllers
- `SaleController` - Sales transaction management
- `PaymentController` - Payment processing

## Models
- `Sale` - Sales transactions
- `SaleItem` - Individual items in sales
- `Payment` - Payment records

## Services
- `SaleService` - Sales business logic
- `PaymentService` - Payment processing logic
- `PerformanceService` - Performance tracking

## Frontend Components
- `POSInterface.vue` - Main POS interface
- `SalesHistory.vue` - Sales history view
- `PaymentHistory.vue` - Payment tracking

## Payment Methods
- Cash payments
- Credit/Debit cards
- Digital payments
- Split payments
- Store credit

## Database Tables
- `sales` - Main sales records
- `sale_items` - Items within each sale
- `payments` - Payment transactions

## DTOs
- `CreateSaleDTO` - Sale creation data transfer
- `SaleItemDTO` - Sale item data transfer
- `SaleResponseDTO` - Sale response formatting