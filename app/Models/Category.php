<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\Models\GenerateCategorySlugAction;
use Carbon\CarbonImmutable;
use Database\Factories\CategoryFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Collection<int, Product> $products
 * @property-read int|null $products_count
 *
 * @method static CategoryFactory factory($count = null, $state = [])
 * @method static Builder<Category>|Category newModelQuery()
 * @method static Builder<Category>|Category newQuery()
 * @method static Builder<Category>|Category query()
 * @method static Builder<Category>|Category whereCreatedAt($value)
 * @method static Builder<Category>|Category whereId($value)
 * @method static Builder<Category>|Category whereName($value)
 * @method static Builder<Category>|Category whereSlug($value)
 * @method static Builder<Category>|Category whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
final class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function booted(): void
    {
        self::creating(function (Category $category): void {
            if (empty($category->slug)) {
                $category->slug = app(GenerateCategorySlugAction::class)->execute($category->name);
            }
        });

        self::updating(function (Category $category): void {
            if ($category->isDirty('name') && ! $category->isDirty('slug')) {
                $category->slug = app(GenerateCategorySlugAction::class)->execute($category->name, $category->id);
            }
        });
    }
}
