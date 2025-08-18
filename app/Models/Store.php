<?php

namespace App\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot(['stock', 'low_stock_threshold'])
            ->withTimestamps();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
