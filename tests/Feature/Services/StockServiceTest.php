<?php

use App\Models\Product;
use App\Models\Store;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->stockService = new StockService();
    $this->product = Product::factory()->create();
    $this->store = Store::factory()->create();
});

it('returns zero stock when product not attached to store', function () {
    $stock = $this->stockService->getStockForStore($this->product->id, $this->store->id);
    
    expect($stock)->toBe(0);
});

it('returns correct stock when product attached to store', function () {
    $this->product->stores()->attach($this->store->id, [
        'stock' => 25,
        'low_stock_threshold' => 5,
    ]);
    
    $stock = $this->stockService->getStockForStore($this->product->id, $this->store->id);
    
    expect($stock)->toBe(25);
});

it('can decrement stock', function () {
    $this->product->stores()->attach($this->store->id, [
        'stock' => 20,
        'low_stock_threshold' => 5,
    ]);
    
    $this->stockService->decrementStock($this->product->id, $this->store->id, 3);
    
    $this->product->refresh();
    $stock = $this->product->stores()->where('stores.id', $this->store->id)->first()->pivot->stock;
    
    expect($stock)->toBe(17);
});

it('prevents stock from going below zero when decrementing', function () {
    $this->product->stores()->attach($this->store->id, [
        'stock' => 2,
        'low_stock_threshold' => 5,
    ]);
    
    $this->stockService->decrementStock($this->product->id, $this->store->id, 5);
    
    $this->product->refresh();
    $stock = $this->product->stores()->where('stores.id', $this->store->id)->first()->pivot->stock;
    
    expect($stock)->toBe(0);
});

it('creates pivot record when decrementing stock for unattached product', function () {
    // Product not attached to store initially
    expect($this->product->stores()->count())->toBe(0);
    
    $this->stockService->decrementStock($this->product->id, $this->store->id, 1);
    
    $this->product->refresh();
    expect($this->product->stores()->count())->toBe(1);
    
    $pivotRecord = $this->product->stores()->where('stores.id', $this->store->id)->first();
    expect($pivotRecord->pivot->stock)->toBe(0);
});

it('can increment stock', function () {
    $this->product->stores()->attach($this->store->id, [
        'stock' => 10,
        'low_stock_threshold' => 5,
    ]);
    
    $this->stockService->incrementStock($this->product->id, $this->store->id, 5);
    
    $this->product->refresh();
    $stock = $this->product->stores()->where('stores.id', $this->store->id)->first()->pivot->stock;
    
    expect($stock)->toBe(15);
});

it('creates pivot record when incrementing stock for unattached product', function () {
    // Product not attached to store initially
    expect($this->product->stores()->count())->toBe(0);
    
    $this->stockService->incrementStock($this->product->id, $this->store->id, 10);
    
    $this->product->refresh();
    expect($this->product->stores()->count())->toBe(1);
    
    $pivotRecord = $this->product->stores()->where('stores.id', $this->store->id)->first();
    expect($pivotRecord->pivot->stock)->toBe(10);
});