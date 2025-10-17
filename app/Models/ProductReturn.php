<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ProductReturnFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property int $sale_id
 * @property int $store_id
 * @property int|null $customer_id
 * @property int $processed_by
 * @property string $reason
 * @property string $refund_method
 * @property numeric $subtotal
 * @property numeric $tax_refund
 * @property numeric $total_refund
 * @property string $status
 * @property string|null $notes
 * @property CarbonImmutable|null $processed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Sale $sale
 * @property-read Store $store
 * @property-read Customer|null $customer
 * @property-read User $processedBy
 * @property-read Collection<int, ReturnItem> $items
 * @property-read int|null $items_count
 *
 * @method static ProductReturnFactory factory($count = null, $state = [])
 * @method static Builder<static>|ProductReturn newModelQuery()
 * @method static Builder<static>|ProductReturn newQuery()
 * @method static Builder<static>|ProductReturn query()
 * @method static Builder<static>|ProductReturn whereCode($value)
 * @method static Builder<static>|ProductReturn whereCreatedAt($value)
 * @method static Builder<static>|ProductReturn whereCustomerId($value)
 * @method static Builder<static>|ProductReturn whereId($value)
 * @method static Builder<static>|ProductReturn whereNotes($value)
 * @method static Builder<static>|ProductReturn whereProcessedAt($value)
 * @method static Builder<static>|ProductReturn whereProcessedBy($value)
 * @method static Builder<static>|ProductReturn whereReason($value)
 * @method static Builder<static>|ProductReturn whereRefundMethod($value)
 * @method static Builder<static>|ProductReturn whereSaleId($value)
 * @method static Builder<static>|ProductReturn whereStatus($value)
 * @method static Builder<static>|ProductReturn whereStoreId($value)
 * @method static Builder<static>|ProductReturn whereSubtotal($value)
 * @method static Builder<static>|ProductReturn whereTaxRefund($value)
 * @method static Builder<static>|ProductReturn whereTotalRefund($value)
 * @method static Builder<static>|ProductReturn whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class ProductReturn extends Model
{
    /** @use HasFactory<ProductReturnFactory> */
    use HasFactory;

    protected $table = 'returns';

    protected $fillable = [
        'code',
        'sale_id',
        'store_id',
        'customer_id',
        'processed_by',
        'reason',
        'refund_method',
        'subtotal',
        'tax_refund',
        'total_refund',
        'status',
        'notes',
        'processed_at',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    // Scopes
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeByStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByCustomer(Builder $query, int $customerId): Builder
    {
        return $query->where('customer_id', $customerId);
    }

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_refund' => 'decimal:2',
            'total_refund' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }
}
