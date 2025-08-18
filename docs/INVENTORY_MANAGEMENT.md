# Inventory Management System

## Overview
Advanced inventory tracking and management system with real-time stock monitoring and automated stock movements.

## Features
- Real-time stock tracking
- Automated stock movements
- Low stock alerts
- Inventory adjustments
- Stock movement history
- Multi-store inventory support

## Controllers
- `StockMovementController` - Handle stock movements
- Integration with `ProductController` for stock updates

## Models
- `StockMovement` - Track all inventory changes
- `Product` - Contains stock quantities
- `Store` - Multi-location inventory

## Services
- `StockService` - Business logic for stock operations
- `InventoryService` - Inventory calculations and reporting

## Frontend Components
- `StockManagement.vue` - Stock management interface
- `InventoryOverview.vue` - Inventory dashboard

## Stock Movement Types
- `purchase` - Stock increases from purchases
- `sale` - Stock decreases from sales
- `adjustment` - Manual stock adjustments
- `transfer` - Inter-store transfers
- `damaged` - Damaged/expired stock removal

## Database Tables
- `stock_movements` - All inventory transactions
- `products` - Current stock levels
- `stores` - Store-specific inventory