# Customer Management System

## Overview
Comprehensive customer relationship management for tracking customer information, purchase history, and loyalty programs.

## Features
- Customer profile management
- Purchase history tracking
- Customer analytics
- Loyalty point system
- Customer communication
- Customer search and filtering

## Controllers
- `CustomerController` - Customer CRUD operations
- Integration with sales for purchase tracking

## Models
- `Customer` - Customer information and relationships
- Relationships with `Sale` for purchase history

## Frontend Components
- `CustomerManagement.vue` - Customer management interface
- Customer search and filtering
- Customer profile views

## Customer Information
- Basic contact details
- Purchase history
- Loyalty points
- Preferred payment methods
- Communication preferences

## Database Tables
- `customers` - Customer information
- `sales` - Linked to customers for purchase history

## Features Integration
- Sales system integration for purchase tracking
- Loyalty point calculations
- Customer analytics and reporting
- Email/SMS communication capabilities

## API Endpoints
- `/api/customers` - Customer CRUD operations
- Customer search and filtering endpoints
- Purchase history retrieval