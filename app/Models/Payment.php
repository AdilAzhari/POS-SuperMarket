<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Observers\PaymentObserver;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([PaymentObserver::class])]
/**
 * @property int $id
 * @property string $payment_code
 * @property int $sale_id
 * @property int $store_id
 * @property int $user_id
 * @property PaymentMethod $payment_method
 * @property PaymentStatus $status
 * @property numeric $amount
 * @property numeric $fee
 * @property numeric $net_amount
 * @property string $currency
 * @property string|null $gateway_transaction_id
 * @property string|null $gateway_reference
 * @property array<array-key, mixed>|null $gateway_response
 * @property string|null $card_last_four
 * @property string|null $card_brand
 * @property string|null $card_exp_month
 * @property string|null $card_exp_year
 * @property string|null $tng_phone
 * @property string|null $tng_reference
 * @property numeric|null $cash_received
 * @property numeric|null $change_amount
 * @property string|null $notes
 * @property CarbonImmutable|null $processed_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Sale $sale
 * @property-read Store $store
 * @property-read User $user
 *
 * @method static PaymentFactory factory($count = null, $state = [])
 * @method static Builder<Payment>|Payment newModelQuery()
 * @method static Builder<Payment>|Payment newQuery()
 * @method static Builder<Payment>|Payment query()
 * @method static Builder<Payment>|Payment whereAmount($value)
 * @method static Builder<Payment>|Payment whereCardBrand($value)
 * @method static Builder<Payment>|Payment whereCardExpMonth($value)
 * @method static Builder<Payment>|Payment whereCardExpYear($value)
 * @method static Builder<Payment>|Payment whereCardLastFour($value)
 * @method static Builder<Payment>|Payment whereCashReceived($value)
 * @method static Builder<Payment>|Payment whereChangeAmount($value)
 * @method static Builder<Payment>|Payment whereCreatedAt($value)
 * @method static Builder<Payment>|Payment whereCurrency($value)
 * @method static Builder<Payment>|Payment whereFee($value)
 * @method static Builder<Payment>|Payment whereGatewayReference($value)
 * @method static Builder<Payment>|Payment whereGatewayResponse($value)
 * @method static Builder<Payment>|Payment whereGatewayTransactionId($value)
 * @method static Builder<Payment>|Payment whereId($value)
 * @method static Builder<Payment>|Payment whereNetAmount($value)
 * @method static Builder<Payment>|Payment whereNotes($value)
 * @method static Builder<Payment>|Payment wherePaymentCode($value)
 * @method static Builder<Payment>|Payment wherePaymentMethod($value)
 * @method static Builder<Payment>|Payment whereProcessedAt($value)
 * @method static Builder<Payment>|Payment whereSaleId($value)
 * @method static Builder<Payment>|Payment whereStatus($value)
 * @method static Builder<Payment>|Payment whereStoreId($value)
 * @method static Builder<Payment>|Payment whereTngPhone($value)
 * @method static Builder<Payment>|Payment whereTngReference($value)
 * @method static Builder<Payment>|Payment whereUpdatedAt($value)
 * @method static Builder<Payment>|Payment whereUserId($value)
 *
 * @mixin Eloquent
 */
final class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'payment_code',
        'sale_id',
        'store_id',
        'user_id',
        'payment_method',
        'status',
        'amount',
        'fee',
        'net_amount',
        'currency',
        'gateway_transaction_id',
        'gateway_reference',
        'gateway_response',
        'card_last_four',
        'card_brand',
        'card_exp_month',
        'card_exp_year',
        'tng_phone',
        'tng_reference',
        'cash_received',
        'change_amount',
        'notes',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'cash_received' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'gateway_response' => 'array',
        'processed_at' => 'datetime',
        'payment_method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsProcessed(): void
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'notes' => $reason,
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }
}
