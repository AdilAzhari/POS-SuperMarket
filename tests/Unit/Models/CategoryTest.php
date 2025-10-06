<?php

declare(strict_types=1);

use App\Models\Category;

beforeEach(function (): void {});

it('returns correct array structure with toArray', function (): void {
    $category = Category::factory()->create()->fresh();

    expect($category)->toBeInstanceOf(Category::class);

    $arrayKeys = array_keys($category->toArray());

    expect($arrayKeys)->toContain(
        'id',
        'name',
        'slug',
        'created_at',
        'updated_at'
    );
});

it('has expected attributes in toArray', function (): void {
    $category = Category::factory()->create([
        'name' => 'Test Category',
        'slug' => 'test-category',
    ])->fresh();

    $categoryArray = $category->toArray();

    expect($categoryArray)
        ->toHaveKeys(['id', 'name', 'slug', 'created_at', 'updated_at'])
        ->and($categoryArray['name'])->toBe('Test Category')
        ->and($categoryArray['slug'])->toBe('test-category');
});

// Test that slug is not regenerated if already provided
it('uses provided slug instead of generating', function (): void {
    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'custom-slug',
    ]);

    expect($category->slug)->toBe('custom-slug');
});

// Test that slug is auto-generated when not provided
it('auto-generates slug when not provided', function (): void {
    $category = Category::create([
        'name' => 'Test Category',
    ]);

    expect($category->slug)
        ->not->toBeNull()
        ->toBe('test-category');
});
