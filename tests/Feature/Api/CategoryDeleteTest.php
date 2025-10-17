<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('can delete a category', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::factory()->create(['name' => 'Test Category']);

    $response = $this->deleteJson("/api/categories/{$category->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

it('can delete a category with products and sets products category_id to null', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $category = Category::factory()->create(['name' => 'Test Category']);
    $product = Product::factory()->create(['category_id' => $category->id]);

    $response = $this->deleteJson("/api/categories/{$category->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('categories', ['id' => $category->id]);

    $product->refresh();
    expect($product->category_id)->toBeNull();
});
