<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns category with products when fetching category details', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::factory()->create(['name' => 'Test Category']);
    Product::factory()->count(5)->create(['category_id' => $category->id]);

    $response = $this->getJson("/api/categories/{$category->id}");

    $response->assertOk();

    $data = $response->json('data');

    expect($data)->toHaveKey('products')
        ->and($data['products'])->toBeArray()
        ->and($data['products'])->toHaveCount(5);
});
