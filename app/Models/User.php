<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
        ];
    }

    // User role helpers
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    public function isCashier(): bool
    {
        return $this->role === 'cashier';
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $permissions = $this->permissions ?? [];

        return in_array($permission, $permissions);
    }

    public function canManageInventory(): bool
    {
        return $this->hasPermission('manage_inventory') || $this->isManager();
    }

    public function canViewReports(): bool
    {
        return $this->hasPermission('view_reports') || $this->isManager();
    }

    public function canManageUsers(): bool
    {
        return $this->hasPermission('manage_users') || $this->isAdmin();
    }

    // Relationships
    public function sales()
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
