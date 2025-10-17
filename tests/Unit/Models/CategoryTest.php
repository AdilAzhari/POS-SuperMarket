<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
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

it('relations', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
    ]);

    expect($category->products()->get()->toArray())->toBeArray()
        ->and($product->category)->toBeInstanceOf(Category::class);

});

it('update category', function () {
    $category_1 = Category::factory()->create();
    $category_2 = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category_1->id,
    ]);

    expect($product->category)->toBeInstanceOf(Category::class)
        ->and($category_2)->toBeInstanceOf(Category::class);

    $product->update(['category_id' => $category_2->id]);

    expect($product->category)->toBeInstanceOf(Category::class)
        ->and($category_1)->toBeInstanceOf(Category::class);

});
