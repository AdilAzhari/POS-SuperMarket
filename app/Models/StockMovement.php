<?php

namespace App\Models;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
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

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($stockMovement) {
            if (!$stockMovement->code) {
                $stockMovement->code = static::generateCode();
            }
        });
    }

    protected static function generateCode(): string
    {
        $prefix = 'SM';
        $lastRecord = static::latest('id')->first();
        $number = $lastRecord ? $lastRecord->id + 1 : 1;
        
        return $prefix . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

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
