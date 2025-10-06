<?php

declare(strict_types=1);

use App\Models\StockMovement;

it('auto-generates code on creation', function (): void {
    $stockMovement = StockMovement::factory()->create(['code' => null]);

    expect($stockMovement->code)
        ->not->toBeNull()
        ->toStartWith('SM-');
});

it('returns correct array structure with toArray', function (): void {
    $stockMovement = StockMovement::factory()->create()->fresh();

    $arrayKeys = array_keys($stockMovement->toArray());

    expect($arrayKeys)->toContain(
        'id',
        'code',
        'product_id',
        'store_id',
        'type',
        'quantity',
        'reason',
        'user_id',
        'occurred_at',
        'created_at',
        'updated_at'
    );
});

it('has correct relationships', function (): void {
    $stockMovement = StockMovement::factory()->create();

    expect($stockMovement->product)->not->toBeNull()
        ->and($stockMovement->store)->not->toBeNull()
        ->and($stockMovement->user)->not->toBeNull();
});
