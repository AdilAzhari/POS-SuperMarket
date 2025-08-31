<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->productService = new ProductService;
    $this->category = Category::factory()->create();
    $this->supplier = Supplier::factory()->create();
});

it('can get paginated products', function (): void {
    Product::factory()->count(25)->create();

    $result = $this->productService->getPaginatedProducts(20);

    expect($result)->toBeInstanceOf(Illuminate\Pagination\LengthAwarePaginator::class)
        ->and($result->count())->toBe(20)
        ->and($result->total())->toBe(25);
});

it('can get product by id', function (): void {
    $product = Product::factory()->create();

    $result = $this->productService->getProductById($product->id);

    expect($result)->toBeInstanceOf(Product::class)
        ->and($result->id)->toBe($product->id)
        ->and($result->relationLoaded('category'))->toBeTrue()
        ->and($result->relationLoaded('supplier'))->toBeTrue()
        ->and($result->relationLoaded('stores'))->toBeTrue();
});

it('can create a product', function (): void {
    $productData = [
        'category_id' => $this->category->id,
        'supplier_id' => $this->supplier->id,
        'name' => 'Test Product',
        'sku' => 'TEST-001',
        'barcode' => '1234567890',
        'price' => 19.99,
        'cost' => 10.00,
        'active' => true,
        'low_stock_threshold' => 5,
        'image_url' => 'https://example.com/image.jpg',
    ];

    $result = $this->productService->createProduct($productData);

    expect($result)->toBeInstanceOf(Product::class)
        ->and($result->name)->toBe('Test Product')
        ->and($result->sku)->toBe('TEST-001')
        ->and((float) $result->price)->toBe(19.99)
        ->and($result->relationLoaded('category'))->toBeTrue()
        ->and($result->relationLoaded('supplier'))->toBeTrue();

    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
        'sku' => 'TEST-001',
        'price' => 19.99,
    ]);
});

it('can update a product', function (): void {
    $product = Product::factory()->create([
        'name' => 'Original Name',
        'price' => 15.00,
    ]);

    $updateData = [
        'name' => 'Updated Name',
        'price' => 25.00,
    ];

    $result = $this->productService->updateProduct($product->id, $updateData);

    expect($result)->toBeInstanceOf(Product::class)
        ->and($result->name)->toBe('Updated Name')
        ->and((float) $result->price)->toBe(25.0);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Updated Name',
        'price' => 25.00,
    ]);
});

it('can delete a product', function (): void {
    $product = Product::factory()->create();

    $result = $this->productService->deleteProduct($product->id);

    expect($result)->toBeTrue();

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});

it('can search products by name', function (): void {
    Product::factory()->create(['name' => 'Apple iPhone']);
    Product::factory()->create(['name' => 'Samsung Galaxy']);
    Product::factory()->create(['name' => 'Apple MacBook']);
    Product::factory()->create(['name' => 'Dell Laptop']);

    $results = $this->productService->searchProducts('Apple');

    expect($results)->toHaveCount(2)
        ->and($results->first()->name)->toContain('Apple');
});

it('can search products by sku', function (): void {
    Product::factory()->create(['sku' => 'APL-001', 'name' => 'Product 1']);
    Product::factory()->create(['sku' => 'SAM-001', 'name' => 'Product 2']);
    Product::factory()->create(['sku' => 'APL-002', 'name' => 'Product 3']);

    $results = $this->productService->searchProducts('APL');

    expect($results)->toHaveCount(2)
        ->and($results->pluck('sku')->toArray())->toContain('APL-001', 'APL-002');
});

it('can search products by category', function (): void {
    $electronics = Category::factory()->create(['name' => 'Electronics']);
    $clothing = Category::factory()->create(['name' => 'Clothing']);

    Product::factory()->create(['category_id' => $electronics->id]);
    Product::factory()->create(['category_id' => $electronics->id]);
    Product::factory()->create(['category_id' => $clothing->id]);

    $results = $this->productService->searchProducts('Electronics');

    expect($results)->toHaveCount(2);
});

it('returns empty collection for empty search query', function (): void {
    Product::factory()->count(5)->create();

    $results = $this->productService->searchProducts('');

    expect($results)->toBeEmpty();
});
