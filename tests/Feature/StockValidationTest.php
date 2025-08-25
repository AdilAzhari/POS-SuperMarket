<?php

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->stockService = new StockService();
    $this->user = User::factory()->create();
    $this->product = Product::factory()->create();
    $this->store = Store::factory()->create();
});

it('validates stock sufficiency when stock is available', function () {
    // Attach product to store with stock
    $this->product->stores()->attach($this->store->id, [
        'stock' => 10,
        'low_stock_threshold' => 5,
    ]);

    $result = $this->stockService->validateStockSufficiency($this->product->id, $this->store->id, 5);

    expect($result)
        ->toBeArray()
        ->and($result['valid'])->toBeTrue()
        ->and($result['available_stock'])->toBe(10)
        ->and($result['requested_quantity'])->toBe(5)
        ->and($result['product_name'])->toBe($this->product->name);
});

it('validates stock sufficiency when stock is insufficient', function () {
    // Attach product to store with limited stock
    $this->product->stores()->attach($this->store->id, [
        'stock' => 3,
        'low_stock_threshold' => 5,
    ]);

    $result = $this->stockService->validateStockSufficiency($this->product->id, $this->store->id, 5);

    expect($result)
        ->toBeArray()
        ->and($result['valid'])->toBeFalse()
        ->and($result['available_stock'])->toBe(3)
        ->and($result['requested_quantity'])->toBe(5)
        ->and($result)->toHaveKey('message');
});

it('validates stock sufficiency when product not attached to store', function () {
    $result = $this->stockService->validateStockSufficiency($this->product->id, $this->store->id, 1);

    expect($result)
        ->toBeArray()
        ->and($result['valid'])->toBeFalse()
        ->and($result['available_stock'])->toBe(0)
        ->and($result['requested_quantity'])->toBe(1);
});

it('checks low stock status when stock is below threshold', function () {
    $this->product->stores()->attach($this->store->id, [
        'stock' => 3,
        'low_stock_threshold' => 5,
    ]);

    $result = $this->stockService->checkLowStock($this->product->id, $this->store->id);

    expect($result)
        ->toBeArray()
        ->and($result['is_low_stock'])->toBeTrue()
        ->and($result['current_stock'])->toBe(3)
        ->and($result['low_stock_threshold'])->toBe(5);
});

it('checks low stock status when stock is above threshold', function () {
    $this->product->stores()->attach($this->store->id, [
        'stock' => 10,
        'low_stock_threshold' => 5,
    ]);

    $result = $this->stockService->checkLowStock($this->product->id, $this->store->id);

    expect($result)
        ->toBeArray()
        ->and($result['is_low_stock'])->toBeFalse()
        ->and($result['current_stock'])->toBe(10)
        ->and($result['low_stock_threshold'])->toBe(5);
});

it('gets product stock summary across multiple stores', function () {
    $store2 = Store::factory()->create();
    
    // Attach to multiple stores with different stock levels
    $this->product->stores()->attach($this->store->id, [
        'stock' => 10,
        'low_stock_threshold' => 5,
    ]);
    
    $this->product->stores()->attach($store2->id, [
        'stock' => 2,
        'low_stock_threshold' => 5,
    ]);

    $result = $this->stockService->getProductStockSummary($this->product->id);

    expect($result)
        ->toBeArray()
        ->and($result['product_id'])->toBe($this->product->id)
        ->and($result['product_name'])->toBe($this->product->name)
        ->and($result['total_stock'])->toBe(12)
        ->and($result['total_stores'])->toBe(2)
        ->and($result['low_stock_stores'])->toBe(1)
        ->and($result['stores'])->toHaveCount(2);
});

it('validates multiple products successfully', function () {
    $product2 = Product::factory()->create();
    
    $this->product->stores()->attach($this->store->id, [
        'stock' => 10,
        'low_stock_threshold' => 5,
    ]);
    
    $product2->stores()->attach($this->store->id, [
        'stock' => 5,
        'low_stock_threshold' => 5,
    ]);

    $items = [
        [
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 5
        ],
        [
            'product_id' => $product2->id,
            'store_id' => $this->store->id,
            'quantity' => 3
        ]
    ];

    $result = $this->stockService->validateMultipleProducts($items);

    expect($result)
        ->toBeArray()
        ->and($result['valid'])->toBeTrue()
        ->and($result['total_items'])->toBe(2)
        ->and($result['items'])->toHaveCount(2)
        ->and($result['invalid_items'])->toBeEmpty();
});

it('validates multiple products with some invalid items', function () {
    $product2 = Product::factory()->create();
    
    $this->product->stores()->attach($this->store->id, [
        'stock' => 10,
        'low_stock_threshold' => 5,
    ]);
    
    $product2->stores()->attach($this->store->id, [
        'stock' => 2,
        'low_stock_threshold' => 5,
    ]);

    $items = [
        [
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 5
        ],
        [
            'product_id' => $product2->id,
            'store_id' => $this->store->id,
            'quantity' => 5  // Insufficient stock
        ]
    ];

    $result = $this->stockService->validateMultipleProducts($items);

    expect($result)
        ->toBeArray()
        ->and($result['valid'])->toBeFalse()
        ->and($result['total_items'])->toBe(2)
        ->and($result['items'])->toHaveCount(2)
        ->and($result['invalid_items'])->toHaveCount(1);
});

it('can validate stock via API endpoint', function () {
    $this->product->stores()->attach($this->store->id, [
        'stock' => 10,
        'low_stock_threshold' => 5,
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/stock-movements/validate', [
        'product_id' => $this->product->id,
        'store_id' => $this->store->id,
        'quantity' => 5
    ]);

    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'available_stock' => 10,
            'requested_quantity' => 5
        ]);
});

it('can check low stock via API endpoint', function () {
    $this->product->stores()->attach($this->store->id, [
        'stock' => 3,
        'low_stock_threshold' => 5,
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/stock-movements/check-low-stock', [
        'product_id' => $this->product->id,
        'store_id' => $this->store->id
    ]);

    $response->assertOk()
        ->assertJson([
            'is_low_stock' => true,
            'current_stock' => 3,
            'low_stock_threshold' => 5
        ]);
});

it('can get product stock summary via API endpoint', function () {
    $this->product->stores()->attach($this->store->id, [
        'stock' => 10,
        'low_stock_threshold' => 5,
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/products/' . $this->product->id . '/stock-summary');

    $response->assertOk()
        ->assertJson([
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'total_stock' => 10,
            'total_stores' => 1,
            'low_stock_stores' => 0
        ]);
});

it('can validate multiple products via API endpoint', function () {
    $product2 = Product::factory()->create();
    
    $this->product->stores()->attach($this->store->id, [
        'stock' => 10,
        'low_stock_threshold' => 5,
    ]);
    
    $product2->stores()->attach($this->store->id, [
        'stock' => 5,
        'low_stock_threshold' => 5,
    ]);

    $response = $this->actingAs($this->user)->postJson('/api/stock-movements/validate-multiple', [
        'items' => [
            [
                'product_id' => $this->product->id,
                'store_id' => $this->store->id,
                'quantity' => 5
            ],
            [
                'product_id' => $product2->id,
                'store_id' => $this->store->id,
                'quantity' => 3
            ]
        ]
    ]);

    $response->assertOk()
        ->assertJson([
            'valid' => true,
            'total_items' => 2
        ]);
});