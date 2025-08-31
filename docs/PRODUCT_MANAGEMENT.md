# Product Management System

## Overview
Comprehensive product management system for the POS application handling products, categories, and suppliers.

## Features
- Product CRUD operations
- Category management
- Supplier management
- Product-store relationships
- Inventory tracking integration

## Controllers
- `ProductController` - Product operations
- `CategoryController` - Category management  
- `SupplierController` - Supplier management

## Models
- `Product` - Product entity with relationships
- `Category` - Product categorization
- `Supplier` - Supplier information and relationships

## Frontend Components
- `ProductManagement.vue` - Product management interface
- `CategoryManagement.vue` - Category management
- `SupplierManagement.vue` - Supplier management

## Database Tables
- `products` - Main product information
- `categories` - Product categories
- `suppliers` - Supplier data
- `product_store` - Many-to-many relationship

## API Endpoints
- `/api/products` - Product CRUD
- `/api/categories` - Category CRUD
- `/api/suppliers` - Supplier CRUD