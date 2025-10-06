<?php

declare(strict_types=1);

namespace App\Actions\Models;

use App\Models\Category;
use Illuminate\Support\Str;

final class GenerateCategorySlugAction
{
    /**
     * Generate a unique slug for the category
     */
    public function execute(string $name, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Category::query()
            ->where('slug', $slug)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
