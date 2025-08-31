<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $purchase_order_id
 * @property int $product_id
 * @property int $quantity_ordered
 * @property int $quantity_received
 * @property numeric $unit_cost
 * @property numeric $total_cost
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Product $product
 * @property-read PurchaseOrder $purchaseOrder
 *
 * @method static Builder<static>|PurchaseOrderItem newModelQuery()
 * @method static Builder<static>|PurchaseOrderItem newQuery()
 * @method static Builder<static>|PurchaseOrderItem query()
 * @method static Builder<static>|PurchaseOrderItem whereCreatedAt($value)
 * @method static Builder<static>|PurchaseOrderItem whereId($value)
 * @method static Builder<static>|PurchaseOrderItem whereNotes($value)
 * @method static Builder<static>|PurchaseOrderItem whereProductId($value)
 * @method static Builder<static>|PurchaseOrderItem wherePurchaseOrderId($value)
 * @method static Builder<static>|PurchaseOrderItem whereQuantityOrdered($value)
 * @method static Builder<static>|PurchaseOrderItem whereQuantityReceived($value)
 * @method static Builder<static>|PurchaseOrderItem whereTotalCost($value)
 * @method static Builder<static>|PurchaseOrderItem whereUnitCost($value)
 * @method static Builder<static>|PurchaseOrderItem whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'total_cost',
        'notes',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Helper methods
    public function calculateTotalCost(): self
    {
        $this->total_cost = $this->quantity_ordered * $this->unit_cost;
        $this->save();

        return $this;
    }

    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }

    public function getRemainingQuantity(): int
    {
        return max(0, $this->quantity_ordered - $this->quantity_received);
    }

    public function receiveQuantity(int $quantity): self
    {
        $this->quantity_received = min(
            $this->quantity_ordered,
            $this->quantity_received + $quantity
        );
        $this->save();

        return $this;
    }

    protected static function boot(): void
    {
        parent::boot();

        self::creating(function ($item): void {
            if (empty($item->total_cost) && $item->quantity_ordered && $item->unit_cost) {
                $item->total_cost = $item->quantity_ordered * $item->unit_cost;
            }
        });

        self::updating(function ($item): void {
            if ($item->isDirty(['quantity_ordered', 'unit_cost'])) {
                $item->total_cost = $item->quantity_ordered * $item->unit_cost;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'integer',
            'quantity_received' => 'integer',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }
}
