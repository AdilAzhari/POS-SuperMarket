<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'store_id',
        'customer_id',
        'cashier_id',
        'items_count',
        'subtotal',
        'discount',
        'tax',
        'total',
        'payment_method',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'items_count' => 'integer',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'payment_method' => PaymentMethod::class,
        'status' => SaleStatus::class,
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    // Query Scopes
    #[Scope]
    public function completed($query)
    {
        return $query->where('status', SaleStatus::COMPLETED);
    }

    #[Scope]
    public function byStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }
    #[Scope]
    public function byCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
    #[Scope]
    public function byCashier($query, $cashierId)
    {
        return $query->where('cashier_id', $cashierId);
    }
    #[Scope]
    public function byPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }
    #[Scope]
    public function inDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
    #[Scope]
    public function today($query)
    {
        return $query->whereDate('created_at', today());
    }
    #[Scope]
    public function thisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }
    #[Scope]
    public function thisMonth($query)
    {
        return $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    // Helper methods
    public function getTotalWithoutTax(): float
    {
        return $this->subtotal - $this->discount;
    }

    public function getItemsWeight(): int
    {
        return $this->items->sum('quantity');
    }
}
