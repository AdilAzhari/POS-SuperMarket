<?php

declare(strict_types=1);

use App\Actions\Stock\ProcessStockMovementAction;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;

beforeEach(function (): void {
    $this->action = new ProcessStockMovementAction;
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->product = Product::factory()->create();

    // Attach product to store with initial stock of 50
    $this->product->stores()->attach($this->store->id, [
        'stock' => 50,
        'low_stock_threshold' => 10,
    ]);
});

test('it processes addition movement correctly', function (): void {
    $data = [
        'product_id' => $this->product->id,
        'store_id' => $this->store->id,
        'type' => 'addition',
        'quantity' => 20,
        'reason' => 'purchase',
        'user_id' => $this->user->id,
    ];

    $movement = $this->action->execute($data);

    expect($movement)->toBeInstanceOf(StockMovement::class)
        ->and($movement->type->value)->toBe('addition')
        ->and($movement->quantity)->toBe(20);

    // Check stock was increased: 50 + 20 = 70
    $updatedStock = $this->product->fresh()->stores()->where('stores.id', $this->store->id)->first()->pivot->stock;
    expect($updatedStock)->toBe(70);
});

test('it processes reduction movement correctly', function (): void {
    $data = [
        'product_id' => $this->product->id,
        'store_id' => $this->store->id,
        'type' => 'reduction',
        'quantity' => 15,
        'reason' => 'sale',
        'user_id' => $this->user->id,
    ];

    $movement = $this->action->execute($data);

    expect($movement)->toBeInstanceOf(StockMovement::class)
        ->and($movement->type->value)->toBe('reduction')
        ->and($movement->quantity)->toBe(15);

    // Check stock was decreased: 50 - 15 = 35
    $updatedStock = $this->product->fresh()->stores()->where('stores.id', $this->store->id)->first()->pivot->stock;
    expect($updatedStock)->toBe(35);
});

test('it processes adjustment movement correctly by setting absolute stock level', function (): void {
    $data = [
        'product_id' => $this->product->id,
        'store_id' => $this->store->id,
        'type' => 'adjustment',
        'quantity' => 75,
        'reason' => 'recount',
        'user_id' => $this->user->id,
    ];

    $movement = $this->action->execute($data);

    expect($movement)->toBeInstanceOf(StockMovement::class)
        ->and($movement->type->value)->toBe('adjustment')
        ->and($movement->quantity)->toBe(75);

    // Check stock was set to absolute value: 75 (not 50 + 75)
    $updatedStock = $this->product->fresh()->stores()->where('stores.id', $this->store->id)->first()->pivot->stock;
    expect($updatedStock)->toBe(75);
});

test('it processes adjustment movement correctly when adjusting down', function (): void {
    $data = [
        'product_id' => $this->product->id,
        'store_id' => $this->store->id,
        'type' => 'adjustment',
        'quantity' => 30,
        'reason' => 'recount',
        'user_id' => $this->user->id,
    ];

    $movement = $this->action->execute($data);

    expect($movement)->toBeInstanceOf(StockMovement::class);

    // Check stock was set to absolute value: 30 (not 50 - 30)
    $updatedStock = $this->product->fresh()->stores()->where('stores.id', $this->store->id)->first()->pivot->stock;
    expect($updatedStock)->toBe(30);
});

test('it prevents stock from going below zero', function (): void {
    $data = [
        'product_id' => $this->product->id,
        'store_id' => $this->store->id,
        'type' => 'reduction',
        'quantity' => 100,
        'reason' => 'sale',
        'user_id' => $this->user->id,
    ];

    $movement = $this->action->execute($data);

    // Check stock was clamped to 0: max(0, 50 - 100) = 0
    $updatedStock = $this->product->fresh()->stores()->where('stores.id', $this->store->id)->first()->pivot->stock;
    expect($updatedStock)->toBe(0);
});

test('it creates pivot record for product not yet attached to store', function (): void {
    $newStore = Store::factory()->create();

    $data = [
        'product_id' => $this->product->id,
        'store_id' => $newStore->id,
        'type' => 'addition',
        'quantity' => 25,
        'reason' => 'purchase',
        'user_id' => $this->user->id,
    ];

    $movement = $this->action->execute($data);

    expect($movement)->toBeInstanceOf(StockMovement::class);

    // Check pivot was created with correct stock: 0 + 25 = 25
    $updatedStock = $this->product->fresh()->stores()->where('stores.id', $newStore->id)->first()->pivot->stock;
    expect($updatedStock)->toBe(25);
});
