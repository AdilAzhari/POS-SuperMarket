<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Observers\StockMovementObserver;
use Carbon\CarbonImmutable;
use Database\Factories\StockMovementFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([StockMovementObserver::class])]
/**
 * @property int $id
 * @property string $code
 * @property int $product_id
 * @property int $store_id
 * @property StockMovementType $type
 * @property int $quantity
 * @property StockMovementReason $reason
 * @property string|null $notes
 * @property int|null $from_store_id
 * @property int|null $to_store_id
 * @property int $user_id
 * @property CarbonImmutable $occurred_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Store|null $fromStore
 * @property-read Product $product
 * @property-read Store $store
 * @property-read Store|null $toStore
 * @property-read User $user
 *
 * @method static StockMovementFactory factory($count = null, $state = [])
 * @method static Builder<StockMovement>|StockMovement newModelQuery()
 * @method static Builder<StockMovement>|StockMovement newQuery()
 * @method static Builder<StockMovement>|StockMovement query()
 * @method static Builder<StockMovement>|StockMovement whereCode($value)
 * @method static Builder<StockMovement>|StockMovement whereCreatedAt($value)
 * @method static Builder<StockMovement>|StockMovement whereFromStoreId($value)
 * @method static Builder<StockMovement>|StockMovement whereId($value)
 * @method static Builder<StockMovement>|StockMovement whereNotes($value)
 * @method static Builder<StockMovement>|StockMovement whereOccurredAt($value)
 * @method static Builder<StockMovement>|StockMovement whereProductId($value)
 * @method static Builder<StockMovement>|StockMovement whereQuantity($value)
 * @method static Builder<StockMovement>|StockMovement whereReason($value)
 * @method static Builder<StockMovement>|StockMovement whereStoreId($value)
 * @method static Builder<StockMovement>|StockMovement whereToStoreId($value)
 * @method static Builder<StockMovement>|StockMovement whereType($value)
 * @method static Builder<StockMovement>|StockMovement whereUpdatedAt($value)
 * @method static Builder<StockMovement>|StockMovement whereUserId($value)
 *
 * @mixin Eloquent
 */
final class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'product_id',
        'store_id',
        'type',
        'quantity',
        'reason',
        'notes',
        'from_store_id',
        'to_store_id',
        'user_id',
        'occurred_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'occurred_at' => 'datetime',
        'type' => StockMovementType::class,
        'reason' => StockMovementReason::class,
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
