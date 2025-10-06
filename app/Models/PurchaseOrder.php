<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PurchaseOrderFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $po_number
 * @property int $supplier_id
 * @property int $store_id
 * @property int $created_by
 * @property PurchaseOrderStatus $status
 * @property numeric $total_amount
 * @property string|null $notes
 * @property CarbonImmutable|null $ordered_at
 * @property CarbonImmutable|null $expected_delivery_at
 * @property CarbonImmutable|null $received_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $createdBy
 * @property-read Collection<int, PurchaseOrderItem> $items
 * @property-read int|null $items_count
 * @property-read Store $store
 * @property-read Supplier $supplier
 *
 * @method static Builder<static>|PurchaseOrder byStatus(string $status)
 * @method static Builder<static>|PurchaseOrder byStore(int $storeId)
 * @method static Builder<static>|PurchaseOrder bySupplier(int $supplierId)
 * @method static PurchaseOrderFactory factory($count = null, $state = [])
 * @method static Builder<static>|PurchaseOrder newModelQuery()
 * @method static Builder<static>|PurchaseOrder newQuery()
 * @method static Builder<static>|PurchaseOrder pending()
 * @method static Builder<static>|PurchaseOrder query()
 * @method static Builder<static>|PurchaseOrder whereCreatedAt($value)
 * @method static Builder<static>|PurchaseOrder whereCreatedBy($value)
 * @method static Builder<static>|PurchaseOrder whereExpectedDeliveryAt($value)
 * @method static Builder<static>|PurchaseOrder whereId($value)
 * @method static Builder<static>|PurchaseOrder whereNotes($value)
 * @method static Builder<static>|PurchaseOrder whereOrderedAt($value)
 * @method static Builder<static>|PurchaseOrder wherePoNumber($value)
 * @method static Builder<static>|PurchaseOrder whereReceivedAt($value)
 * @method static Builder<static>|PurchaseOrder whereStatus($value)
 * @method static Builder<static>|PurchaseOrder whereStoreId($value)
 * @method static Builder<static>|PurchaseOrder whereSupplierId($value)
 * @method static Builder<static>|PurchaseOrder whereTotalAmount($value)
 * @method static Builder<static>|PurchaseOrder whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'po_number',
        'supplier_id',
        'store_id',
        'created_by',
        'status',
        'total_amount',
        'notes',
        'ordered_at',
        'expected_delivery_at',
        'received_at',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    // Scopes
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeByStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            PurchaseOrderStatus::DRAFT,
            PurchaseOrderStatus::PENDING,
            PurchaseOrderStatus::ORDERED,
        ]);
    }

    // Helper methods
    public function calculateTotal(): self
    {
        $this->total_amount = $this->items->sum('total_cost');
        $this->save();

        return $this;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [PurchaseOrderStatus::DRAFT, PurchaseOrderStatus::PENDING]);
    }

    public function canBeReceived(): bool
    {
        return in_array($this->status, [PurchaseOrderStatus::ORDERED, PurchaseOrderStatus::PARTIAL]);
    }

    public function markAsOrdered(): self
    {
        $this->update([
            'status' => PurchaseOrderStatus::ORDERED,
            'ordered_at' => now(),
        ]);

        return $this;
    }

    public function markAsReceived(): self
    {
        $allItemsReceived = $this->items->every(fn ($item): bool => $item->quantity_received >= $item->quantity_ordered);

        $this->update([
            'status' => $allItemsReceived ? PurchaseOrderStatus::RECEIVED : PurchaseOrderStatus::PARTIAL,
            'received_at' => $allItemsReceived ? now() : null,
        ]);

        return $this;
    }

    public function getProgress(): array
    {
        $totalOrdered = $this->items->sum('quantity_ordered');
        $totalReceived = $this->items->sum('quantity_received');

        return [
            'ordered' => $totalOrdered,
            'received' => $totalReceived,
            'percentage' => $totalOrdered > 0 ? round(($totalReceived / $totalOrdered) * 100, 1) : 0,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($purchaseOrder): void {
            if (empty($purchaseOrder->po_number)) {
                $purchaseOrder->po_number = self::generatePoNumber();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'ordered_at' => 'datetime',
            'expected_delivery_at' => 'datetime',
            'received_at' => 'datetime',
            'status' => PurchaseOrderStatus::class,
        ];
    }

    private static function generatePoNumber(): string
    {
        $lastPo = self::query()->latest('id')->first();
        $nextId = $lastPo ? $lastPo->id + 1 : 1;

        return 'PO-'.date('Y').'-'.mb_str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }
}
