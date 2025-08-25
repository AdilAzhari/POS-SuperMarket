<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
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
        return $this->type === 'earned';
    }

    public function isRedeemed(): bool
    {
        return $this->type === 'redeemed';
    }
}
