<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
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
        'notes',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'gateway_response' => 'array',
        'processed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($payment) {
            if (!$payment->payment_code) {
                $payment->payment_code = static::generatePaymentCode();
            }
            
            // Calculate net amount after fees
            $payment->net_amount = $payment->amount - $payment->fee;
        });
    }

    protected static function generatePaymentCode(): string
    {
        $prefix = 'PAY';
        $lastRecord = static::latest('id')->first();
        $number = $lastRecord ? $lastRecord->id + 1 : 1;
        
        return $prefix . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

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
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
