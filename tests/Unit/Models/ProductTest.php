<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns correct array structure with toArray', function () {
    $product = Product::factory()->create();

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->toArray())->toBeArray();

    $arrayKeys = array_keys($product->toArray());
    expect($arrayKeys)->toContain(
        'category_id',
        'supplier_id',
        'name',
        'sku',
        'barcode',
        'price',
        'cost',
        'active',
        'low_stock_threshold',
        'image_url'
    );

});

it('relations', function () {
    $category = Category::factory()->create();
    $supplier = Supplier::factory()->create();

    $product = Product::factory()->create([
        'category_id' => $category->id,
        'supplier_id' => $supplier->id,
    ]);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->category)->toBeInstanceOf(Category::class)
        ->and($product->supplier)->toBeInstanceOf(Supplier::class);
});

it('can filter active products', function () {
    $active = Product::factory()->create(['active' => true]);
    $inactive = Product::factory()->create(['active' => false]);

    $activeProduct = Product::query()->active()->get();

    expect($activeProduct)->toHaveCount(1)
        ->and($activeProduct->firstOrFail()->id)->tobe($active->id)
        ->and($activeProduct->firstOrFail()->active)->toBe(true);
});

it('can filter inactive products', function () {
    $active = Product::factory()->create(['active' => true]);
    $inactive = Product::factory()->create(['active' => false]);

    $inactiveProduct = Product::query()->inactive()->get();

    expect($inactiveProduct)->toHaveCount(1)
        ->and($inactiveProduct->firstOrFail()->id)->tobe($inactive->id)
        ->and($inactiveProduct->firstOrFail()->active)->toBe(false);
});
