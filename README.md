# 🏪 POS SuperMarket

> A comprehensive Point of Sale (POS) system built with Laravel 11 and Vue.js 3

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat&logo=laravel)](https://laravel.com)
[![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=flat&logo=vue.js)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-1.x-9553E9?style=flat&logo=inertia)](https://inertiajs.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-3.x-06B6D4?style=flat&logo=tailwindcss)](https://tailwindcss.com)
[![License](https://img.shields.io/github/license/AdilAzhari/POS-SuperMarket)](LICENSE)

## 📋 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Usage](#-usage)
- [API Documentation](#-api-documentation)
- [Project Structure](#-project-structure)
- [Development](#-development)
- [Testing](#-testing)
- [Contributing](#-contributing)
- [License](#-license)

## ✨ Features

### 🔐 Authentication System
- Secure user registration and login
- Password reset and email verification
- Role-based access control
- Session management

### 🛍️ Product Management
- Complete CRUD operations for products
- Category hierarchy management
- Supplier relationship tracking
- Product-store associations
- Advanced search and filtering

### 📦 Inventory Management
- Real-time stock level tracking
- Automated stock movement logging
- Low stock alerts and notifications
- Multi-store inventory support
- Stock adjustment capabilities

### 💰 Sales System
- Interactive POS interface
- Multi-payment method support
- Transaction processing
- Receipt generation
- Sales history tracking
- Refund processing

### 👥 Customer Management
- Customer profile management
- Purchase history tracking
- Customer analytics
- Loyalty point system
- Communication preferences

### 📊 Reports & Analytics
- Sales performance analytics
- Inventory reporting
- Customer insights
- Financial reporting
- Real-time dashboards
- Export capabilities (PDF, Excel, CSV)

### ⚙️ System Settings
- Centralized configuration management
- Business rules customization
- Tax configuration
- Store information management
- User permissions
- Backup settings

## 🛠️ Tech Stack

### Backend
- **Laravel 11** - PHP web framework
- **PHP 8.2+** - Server-side programming
- **MySQL** - Database management
- **Redis** - Caching and sessions

### Frontend
- **Vue.js 3** - Progressive JavaScript framework
- **Inertia.js** - Modern monolith approach
- **Tailwind CSS** - Utility-first CSS framework
- **Vite** - Build tool and dev server

### Development Tools
- **PHPUnit** - PHP testing framework
- **Pest** - Testing framework
- **Laravel Telescope** - Debug assistant
- **Composer** - PHP dependency manager
- **NPM** - Node.js package manager

## 🚀 Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL
- Redis (optional)

### Clone Repository
```bash
git clone https://github.com/AdilAzhari/POS-SuperMarket.git
cd POS-SuperMarket
```

### Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### Environment Setup
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Database Setup
```bash
# Run migrations
php artisan migrate

# Seed database with sample data
php artisan db:seed
```

### Build Assets
```bash
# Development build
npm run dev

# Production build
npm run build
```

### Start Development Server
```bash
# Start Laravel development server
php artisan serve

# Start Vite development server (in another terminal)
npm run dev
```

Visit `http://localhost:8000` to access the application.

## ⚙️ Configuration

### Environment Variables
Configure your `.env` file with the following key settings:

```env
# Application
APP_NAME="POS SuperMarket"
APP_ENV=local
APP_KEY=base64:your-app-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_supermarket
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
```

### Database Configuration
Create a MySQL database and update your `.env` file with the correct credentials.

### Performance Optimization
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

## 💻 Usage

### Default Login Credentials
After seeding the database:
- **Email**: admin@example.com
- **Password**: password

### Core Workflows

#### 1. Product Management
1. Navigate to **Products** section
2. Add categories and suppliers
3. Create products with pricing and inventory
4. Assign products to stores

#### 2. Sales Process
1. Open **POS Interface**
2. Search and add products to cart
3. Apply discounts if needed
4. Process payment
5. Print receipt

#### 3. Inventory Tracking
1. Monitor stock levels in **Inventory**
2. Receive alerts for low stock
3. Process stock movements
4. Generate inventory reports

#### 4. Customer Management
1. Add customer profiles
2. Track purchase history
3. Manage loyalty points
4. Generate customer analytics

## 📚 API Documentation

### Authentication Endpoints
```
POST   /api/login          # User login
POST   /api/register       # User registration
POST   /api/logout         # User logout
POST   /api/forgot-password # Password reset
```

### Product Endpoints
```
GET    /api/products       # Get all products
POST   /api/products       # Create product
GET    /api/products/{id}  # Get specific product
PUT    /api/products/{id}  # Update product
DELETE /api/products/{id}  # Delete product
```

### Sales Endpoints
```
GET    /api/sales          # Get all sales
POST   /api/sales          # Create sale
GET    /api/sales/{id}     # Get specific sale
PUT    /api/sales/{id}     # Update sale
DELETE /api/sales/{id}     # Delete sale
```

### Inventory Endpoints
```
GET    /api/stock-movements    # Get stock movements
POST   /api/stock-movements   # Create stock movement
GET    /api/inventory-report  # Get inventory report
```

## 📁 Project Structure

```
POS-SuperMarket/
├── app/
│   ├── DTOs/                 # Data Transfer Objects
│   ├── Exceptions/           # Custom Exceptions
│   ├── Http/
│   │   ├── Controllers/      # Application Controllers
│   │   ├── Middleware/       # Custom Middleware
│   │   └── Requests/         # Form Request Validation
│   ├── Models/               # Eloquent Models
│   ├── Services/             # Business Logic Services
│   └── Observers/            # Model Observers
├── database/
│   ├── factories/            # Model Factories
│   ├── migrations/           # Database Migrations
│   └── seeders/             # Database Seeders
├── resources/
│   ├── js/
│   │   ├── Components/       # Vue Components
│   │   ├── Pages/           # Inertia Pages
│   │   ├── Layouts/         # Layout Components
│   │   ├── stores/          # State Management
│   │   └── utils/           # Utility Functions
│   ├── css/                 # Stylesheets
│   └── views/               # Blade Templates
├── routes/
│   ├── web.php              # Web Routes
│   ├── api.php              # API Routes
│   └── auth.php             # Authentication Routes
├── tests/
│   ├── Feature/             # Feature Tests
│   └── Unit/                # Unit Tests
└── docs/                    # Documentation
```

## 🔧 Development

### Code Standards
- Follow PSR-12 coding standards for PHP
- Use ESLint and Prettier for JavaScript
- Write meaningful commit messages
- Add tests for new features

### Git Workflow
```bash
# Create feature branch
git checkout -b feature/new-feature

# Make changes and commit
git add .
git commit -m "feat: add new feature"

# Push to GitHub
git push origin feature/new-feature

# Create pull request on GitHub
```

### Feature Branches
- `feature/authentication` - Authentication system
- `feature/product-management` - Product management
- `feature/inventory-management` - Inventory tracking
- `feature/sales-system` - POS and sales
- `feature/customer-management` - Customer CRM
- `feature/reports-analytics` - Reporting system
- `feature/system-settings` - Configuration management

### Database Migrations
```bash
# Create new migration
php artisan make:migration create_table_name

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback
```

### Running in Development
```bash
# Start all services
php artisan serve &
npm run dev &
redis-server &
```

## 🧪 Testing

### Run Tests
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

### Test Structure
```
tests/
├── Feature/
│   ├── Api/
│   │   ├── ProductControllerTest.php
│   │   └── SaleControllerTest.php
│   ├── Auth/
│   └── Services/
└── Unit/
    └── Services/
```

### Writing Tests
```php
// Feature Test Example
public function test_can_create_product()
{
    $response = $this->postJson('/api/products', [
        'name' => 'Test Product',
        'price' => 10.99,
        'category_id' => 1,
    ]);

    $response->assertStatus(201);
}
```

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Setup
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

### Code Review Process
1. All pull requests require review
2. Automated tests must pass
3. Code must follow project standards
4. Documentation must be updated

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙋‍♂️ Support

If you encounter any issues or have questions:

1. Check the [documentation](docs/)
2. Search existing [issues](https://github.com/AdilAzhari/POS-SuperMarket/issues)
3. Create a new issue if needed
4. Join our community discussions

## 📸 Screenshots

### Dashboard
![Dashboard](docs/screenshots/dashboard.png)

### POS Interface
![POS Interface](docs/screenshots/pos-interface.png)

### Product Management
![Product Management](docs/screenshots/product-management.png)

### Reports & Analytics
![Reports](docs/screenshots/reports.png)

---

<div align="center">
  <p>Built with ❤️ using Laravel and Vue.js</p>
  <p>
    <a href="https://laravel.com">Laravel</a> •
    <a href="https://vuejs.org">Vue.js</a> •
    <a href="https://inertiajs.com">Inertia.js</a> •
    <a href="https://tailwindcss.com">Tailwind CSS</a>
  </p>
</div>