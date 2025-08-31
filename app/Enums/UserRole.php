<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case SUPERVISOR = 'supervisor';
    case CASHIER = 'cashier';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($role) => [$role->value => $role->getDisplayName()])
            ->toArray();
    }

    public function getDisplayName(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::MANAGER => 'Manager',
            self::SUPERVISOR => 'Supervisor',
            self::CASHIER => 'Cashier',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::ADMIN => 'Full system access and management',
            self::MANAGER => 'Store management and reporting',
            self::SUPERVISOR => 'Team supervision and operations',
            self::CASHIER => 'Point of sale operations',
        };
    }

    public function getPermissions(): array
    {
        return match ($this) {
            self::ADMIN => [
                'view_reports',
                'manage_inventory',
                'manage_users',
                'manage_settings',
                'manage_stores',
                'view_analytics',
                'export_reports',
            ],
            self::MANAGER => [
                'view_reports',
                'manage_inventory',
                'manage_users',
                'view_analytics',
                'export_reports',
            ],
            self::SUPERVISOR => [
                'view_reports',
                'manage_inventory',
                'view_analytics',
            ],
            self::CASHIER => [
                'process_sales',
                'manage_customers',
            ],
        };
    }
}
