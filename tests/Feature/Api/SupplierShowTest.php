<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns supplier with products when fetching supplier details', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $supplier = Supplier::factory()->create(['name' => 'Test Supplier']);
    Product::factory()->count(5)->create(['supplier_id' => $supplier->id]);

    $response = $this->getJson("/api/suppliers/{$supplier->id}");

    $response->assertOk();

    $data = $response->json('data');

    expect($data)->toHaveKey('products')
        ->and($data['products'])->toBeArray()
        ->and($data['products'])->toHaveCount(5);
});
