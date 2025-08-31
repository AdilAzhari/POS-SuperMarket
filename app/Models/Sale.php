<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\SaleStatus;
use Carbon\CarbonImmutable;
use Database\Factories\SaleFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $code
 * @property int $store_id
 * @property int|null $customer_id
 * @property int $cashier_id
 * @property-read int|null $items_count
 * @property numeric $subtotal
 * @property numeric $discount
 * @property numeric $tax
 * @property numeric $total
 * @property PaymentMethod $payment_method
 * @property SaleStatus $status
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $cashier
 * @property-read Customer|null $customer
 * @property-read Collection<int, SaleItem> $items
 * @property-read Payment|null $latestPayment
 * @property-read Collection<int, Payment> $payments
 * @property-read int|null $payments_count
 * @property-read Store $store
 *
 * @method static Builder<static>|Sale byCustomer($customerId)
 * @method static Builder<static>|Sale completed()
 * @method static SaleFactory factory($count = null, $state = [])
 * @method static Builder<static>|Sale inDateRange($startDate, $endDate)
 * @method static Builder<static>|Sale newModelQuery()
 * @method static Builder<static>|Sale newQuery()
 * @method static Builder<static>|Sale query()
 * @method static Builder<static>|Sale today()
 * @method static Builder<static>|Sale whereCashierId($value)
 * @method static Builder<static>|Sale whereCode($value)
 * @method static Builder<static>|Sale whereCreatedAt($value)
 * @method static Builder<static>|Sale whereCustomerId($value)
 * @method static Builder<static>|Sale whereDiscount($value)
 * @method static Builder<static>|Sale whereId($value)
 * @method static Builder<static>|Sale whereItemsCount($value)
 * @method static Builder<static>|Sale wherePaidAt($value)
 * @method static Builder<static>|Sale wherePaymentMethod($value)
 * @method static Builder<static>|Sale whereStatus($value)
 * @method static Builder<static>|Sale whereStoreId($value)
 * @method static Builder<static>|Sale whereSubtotal($value)
 * @method static Builder<static>|Sale whereTax($value)
 * @method static Builder<static>|Sale whereTotal($value)
 * @method static Builder<static>|Sale whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class Sale extends Model
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
    public function scopeCompleted($query)
    {
        return $query->where('status', SaleStatus::COMPLETED);
    }

    public function ScopebyStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function ScopeByCashier($query, $cashierId)
    {
        return $query->where('cashier_id', $cashierId);
    }

    public function ScopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function ScopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function ScopeThisMonth($query)
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
