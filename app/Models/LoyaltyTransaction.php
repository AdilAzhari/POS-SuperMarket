<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LoyaltyTransactionType;
use Carbon\CarbonImmutable;
use Database\Factories\LoyaltyTransactionFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $sale_id
 * @property LoyaltyTransactionType $type
 * @property int $points
 * @property string $description
 * @property array<array-key, mixed>|null $metadata
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Customer $customer
 * @property-read Sale|null $sale
 *
 * @method static LoyaltyTransactionFactory factory($count = null, $state = [])
 * @method static Builder<static>|LoyaltyTransaction newModelQuery()
 * @method static Builder<static>|LoyaltyTransaction newQuery()
 * @method static Builder<static>|LoyaltyTransaction query()
 * @method static Builder<static>|LoyaltyTransaction whereCreatedAt($value)
 * @method static Builder<static>|LoyaltyTransaction whereCustomerId($value)
 * @method static Builder<static>|LoyaltyTransaction whereDescription($value)
 * @method static Builder<static>|LoyaltyTransaction whereExpiresAt($value)
 * @method static Builder<static>|LoyaltyTransaction whereId($value)
 * @method static Builder<static>|LoyaltyTransaction whereMetadata($value)
 * @method static Builder<static>|LoyaltyTransaction wherePoints($value)
 * @method static Builder<static>|LoyaltyTransaction whereSaleId($value)
 * @method static Builder<static>|LoyaltyTransaction whereType($value)
 * @method static Builder<static>|LoyaltyTransaction whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'sale_id',
        'type',
        'points',
        'description',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'points' => 'integer',
        'metadata' => 'json',
        'expires_at' => 'datetime',
        'type' => LoyaltyTransactionType::class,
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isEarned(): bool
    {
        return $this->type === LoyaltyTransactionType::EARNED;
    }

    public function isRedeemed(): bool
    {
        return $this->type === LoyaltyTransactionType::REDEEMED;
    }
}
