<?php

declare(strict_types=1);

use App\Http\Controllers\CacheController;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => 'admin',
        'name' => 'Test Admin',
    ]);

    $this->store = Store::factory()->create();

    $this->product = Product::factory()->create([
        'name' => 'Test Product',
        'price' => 100.00,
    ]);
});

it('can clear all cache successfully', function () {
    // Set some cache values first
    Cache::put('test_key', 'test_value', 60);
    Cache::tags(['products'])->put('product_cache', 'product_data', 60);

    expect(Cache::get('test_key'))->toBe('test_value')
        ->and(Cache::tags(['products'])->get('product_cache'))->toBe('product_data');

    $controller = new CacheController(
        app(App\Services\ReorderService::class),
        app(App\Services\InventoryAlertService::class),
        app(App\Services\ProductService::class)
    );

    $response = $controller->clearAll();

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData()->success)->toBeTrue()
        ->and(Cache::get('test_key'))->toBeNull();
});

it('can clear cache by tags', function () {
    Cache::tags(['products'])->put('product_cache', 'product_data', 60);
    Cache::tags(['reorder'])->put('reorder_cache', 'reorder_data', 60);
    Cache::put('general_cache', 'general_data', 60);

    $request = new Illuminate\Http\Request;
    $request->merge(['tags' => ['products']]);

    $controller = new CacheController(
        app(App\Services\ReorderService::class),
        app(App\Services\InventoryAlertService::class),
        app(App\Services\ProductService::class)
    );

    $response = $controller->clearByTags($request);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData()->success)->toBeTrue()
        ->and(Cache::tags(['products'])->get('product_cache'))->toBeNull()
        ->and(Cache::tags(['reorder'])->get('reorder_cache'))->toBe('reorder_data') // Should still exist
        ->and(Cache::get('general_cache'))->toBe('general_data'); // Should still exist
});

it('handles cache tag clearing with unsupported drivers gracefully', function () {
    // This test would be more relevant with database cache driver
    // For now, test that it doesn't throw errors
    $request = new Illuminate\Http\Request;
    $request->merge(['tags' => ['products', 'reorder']]);

    $controller = new CacheController(
        app(App\Services\ReorderService::class),
        app(App\Services\InventoryAlertService::class),
        app(App\Services\ProductService::class)
    );

    $response = $controller->clearByTags($request);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData()->success)->toBeTrue();
});

it('can get cache statistics', function () {
    $controller = new CacheController(
        app(App\Services\ReorderService::class),
        app(App\Services\InventoryAlertService::class),
        app(App\Services\ProductService::class)
    );

    $response = $controller->stats();

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData()->success)->toBeTrue()
        ->and($response->getData()->data)->toHaveKeys([
            'current_driver',
            'tagged_cache_supported',
            'common_cache_tags',
        ])
        ->and($response->getData()->data->current_driver)->toBe('array')
        ->and($response->getData()->data->tagged_cache_supported)->toBeTrue();
});

it('can warm up cache successfully', function () {
    $request = new Illuminate\Http\Request;
    $request->merge(['store_id' => $this->store->id]);

    $controller = new CacheController(
        app(App\Services\ReorderService::class),
        app(App\Services\InventoryAlertService::class),
        app(App\Services\ProductService::class)
    );

    $response = $controller->warmup($request);

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData()->success)->toBeTrue()
        ->and($response->getData()->warmed_items)->toBeArray()
        ->and($response->getData()->warmed_items)->not->toBeEmpty();
});

it('validates tags parameter for cache clearing', function () {
    $request = new Illuminate\Http\Request;
    $request->merge(['tags' => 'invalid']); // Should be array

    $controller = new CacheController(
        app(App\Services\ReorderService::class),
        app(App\Services\InventoryAlertService::class),
        app(App\Services\ProductService::class)
    );

    $response = $controller->clearByTags($request);

    expect($response->getStatusCode())->toBe(422); // Validation error
});

it('can access cache stats endpoint with authentication', function () {
    $this->actingAs($this->user)
        ->getJson('/api/cache/stats')
        ->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'current_driver',
                'tagged_cache_supported',
                'common_cache_tags',
            ],
        ]);
});

it('can clear cache via API endpoints', function () {
    $this->actingAs($this->user)
        ->postJson('/api/cache/clear-all')
        ->assertSuccessful()
        ->assertJson(['success' => true]);

    $this->actingAs($this->user)
        ->postJson('/api/cache/clear-tags', ['tags' => ['products']])
        ->assertSuccessful()
        ->assertJson(['success' => true]);
});

it('tests product service caching', function () {
    $productService = app(App\Services\ProductService::class);

    // First call should hit database
    $products1 = $productService->getPaginatedProducts();

    // Second call should hit cache
    $products2 = $productService->getPaginatedProducts();

    expect($products1)->toEqual($products2);

    // Clear cache and verify it's working
    $productService->clearServiceCache();

    // Should work after cache clear
    $products3 = $productService->getPaginatedProducts();
    expect($products3)->toBeArray();
});
