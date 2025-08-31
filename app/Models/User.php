<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property UserRole $role
 * @property bool $is_active
 * @property string|null $employee_id
 * @property numeric|null $hourly_rate
 * @property array<array-key, mixed>|null $permissions
 * @property CarbonImmutable|null $last_login_at
 * @property string|null $phone
 * @property string|null $address
 * @property CarbonImmutable|null $hire_date
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Sale> $sales
 * @property-read int|null $sales_count
 * @property-read Collection<int, StockMovement> $stockMovements
 * @property-read int|null $stock_movements_count
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 *
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User whereAddress($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereEmployeeId($value)
 * @method static Builder<static>|User whereHireDate($value)
 * @method static Builder<static>|User whereHourlyRate($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereIsActive($value)
 * @method static Builder<static>|User whereLastLoginAt($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User wherePermissions($value)
 * @method static Builder<static>|User wherePhone($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereRole($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'employee_id',
        'hourly_rate',
        'permissions',
        'phone',
        'address',
        'hire_date',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // User role helpers
    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isManager(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::MANAGER]);
    }

    public function isCashier(): bool
    {
        return $this->role === UserRole::CASHIER;
    }

    public function isSupervisor(): bool
    {
        return $this->role === UserRole::SUPERVISOR;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // Check role-based permissions from enum
        if ($this->role) {
            $rolePermissions = $this->role->getPermissions();
            if (in_array($permission, $rolePermissions)) {
                return true;
            }
        }

        // Fallback to custom permissions array
        $permissions = $this->permissions ?? [];

        return in_array($permission, $permissions);
    }

    public function canManageInventory(): bool
    {
        if ($this->hasPermission('manage_inventory')) {
            return true;
        }

        return $this->isManager();
    }

    public function canViewReports(): bool
    {
        if ($this->hasPermission('view_reports')) {
            return true;
        }

        return $this->isManager();
    }

    public function canManageUsers(): bool
    {
        if ($this->hasPermission('manage_users')) {
            return true;
        }

        return $this->isAdmin();
    }

    public function canAccessStore(int $storeId): bool
    {
        // For now, assuming users can access all stores
        // This could be enhanced with specific store access control
        return $this->isAdmin();
    }

    public function accessibleStores(): Builder
    {
        if ($this->isAdmin()) {
            return Store::query();
        }

        // For now, return all stores - could be enhanced with specific access control
        return Store::query();
    }

    // Relationships
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'json',
            'last_login_at' => 'datetime',
            'hire_date' => 'date',
            'is_active' => 'boolean',
            'hourly_rate' => 'decimal:2',
            'role' => UserRole::class,
        ];
    }
}
