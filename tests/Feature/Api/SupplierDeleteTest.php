<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('can delete a supplier', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $supplier = Supplier::factory()->create(['name' => 'Test Supplier']);

    $response = $this->deleteJson("/api/suppliers/{$supplier->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
});

it('can delete a supplier with products and sets products supplier_id to null', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $supplier = Supplier::factory()->create(['name' => 'Test Supplier']);
    $product = Product::factory()->create(['supplier_id' => $supplier->id]);

    $response = $this->deleteJson("/api/suppliers/{$supplier->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);

    $product->refresh();
    expect($product->supplier_id)->toBeNull();
});
