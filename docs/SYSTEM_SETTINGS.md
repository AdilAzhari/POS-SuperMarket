# System Settings

## Overview
Centralized system configuration management for customizing POS application behavior, appearance, and business rules.

## Features
- Application configuration
- Business rules settings
- Tax configuration
- Currency settings
- Receipt customization
- User permissions
- Store information
- Backup settings

## Controllers
- `SettingController` - Settings management
- `StoreController` - Store configuration

## Models
- `Setting` - System configuration storage
- `Store` - Store-specific settings

## Frontend Components
- `SystemSettings.vue` - Settings management interface
- Configuration forms and controls

## Setting Categories
- **General Settings**
  - Business name and details
  - Operating hours
  - Contact information
  
- **POS Configuration**
  - Receipt template
  - Default payment methods
  - Discount rules
  
- **Tax Settings**
  - Tax rates and rules
  - Tax calculation methods
  
- **Inventory Settings**
  - Low stock thresholds
  - Automatic reorder points
  
- **User Management**
  - Role permissions
  - Access controls

## Database Tables
- `settings` - System configuration storage
- `stores` - Store-specific information

## Configuration Types
- Boolean settings (on/off)
- Numeric values (tax rates, thresholds)
- Text settings (business name, addresses)
- JSON configuration (complex settings)

## API Endpoints
- `/api/settings` - Settings CRUD operations
- `/api/stores` - Store configuration