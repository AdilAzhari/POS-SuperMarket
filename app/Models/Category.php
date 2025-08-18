<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (!$category->slug) {
                $category->slug = static::generateUniqueSlug($category->name);
            }
        });
        
        static::updating(function ($category) {
            if ($category->isDirty('name') && !$category->isDirty('slug')) {
                $category->slug = static::generateUniqueSlug($category->name, $category->id);
            }
        });
    }

    protected static function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;
        
        while (static::where('slug', $slug)
                     ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
                     ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
