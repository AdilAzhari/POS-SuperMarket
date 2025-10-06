<?php

declare(strict_types=1);

use App\Models\Category;

beforeEach(function (): void {});

it('to array', function (): void {
    $category = Category::factory()->create()->fresh();

    //    expect($category)->toBeInstanceOf(Category::class)
    //    ->expect(array_keys($category->toArray()))->toBe([
    //        'id',
    //        'name',
    //        'slug'
    //        ]);
});
