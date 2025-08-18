<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    
    $this->category = Category::factory()->create();
    $this->supplier = Supplier::factory()->create();
});

it('can list products', function () {
    Product::factory()->count(3)->create();
    
    $response = $this->getJson('/api/products');
    
    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'sku',
                    'barcode',
                    'price',
                    'cost',
                    'active',
                    'category',
                    'supplier',
                ]
            ],
            'current_page',
            'per_page',
            'total',
        ]);
});

it('can show a specific product', function () {
    $product = Product::factory()->create();
    
    $response = $this->getJson("/api/products/{$product->id}");
    
    $response->assertOk()
        ->assertJson([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
        ]);
});

it('can create a product', function () {
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
    
    $response = $this->postJson('/api/products', $productData);
    
    $response->assertCreated()
        ->assertJson([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 19.99,
        ]);
    
    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
        'sku' => 'TEST-001',
    ]);
});

it('validates required fields when creating product', function () {
    $response = $this->postJson('/api/products', []);
    
    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'sku', 'price']);
});

it('can update a product', function () {
    $product = Product::factory()->create([
        'name' => 'Original Name',
        'price' => 15.00,
    ]);
    
    $updateData = [
        'name' => 'Updated Name',
        'price' => 25.00,
    ];
    
    $response = $this->putJson("/api/products/{$product->id}", $updateData);
    
    $response->assertOk()
        ->assertJson([
            'name' => 'Updated Name',
            'price' => 25.00,
        ]);
    
    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Updated Name',
        'price' => 25.00,
    ]);
});

it('can delete a product', function () {
    $product = Product::factory()->create();
    
    $response = $this->deleteJson("/api/products/{$product->id}");
    
    $response->assertNoContent();
    
    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});

it('can search products', function () {
    Product::factory()->create(['name' => 'Apple iPhone']);
    Product::factory()->create(['name' => 'Samsung Galaxy']);
    Product::factory()->create(['name' => 'Apple MacBook']);
    
    $response = $this->getJson('/api/products/search?q=Apple');
    
    $response->assertOk()
        ->assertJsonCount(2);
});

it('returns 404 for non-existent product', function () {
    $response = $this->getJson('/api/products/999');
    
    $response->assertNotFound();
});