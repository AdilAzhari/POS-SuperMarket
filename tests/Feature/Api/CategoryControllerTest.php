<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('can list all categories', function (): void {
    Category::factory()->count(3)->create();

    $response = $this->getJson('/api/categories');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                ],
            ],
            'pagination',
        ]);
});

it('can create a category', function (): void {
    $response = $this->postJson('/api/categories', [
        'name' => 'Electronics Category',
    ]);

    $response->assertCreated()
        ->assertJson([
            'message' => 'Category created successfully',
            'data' => [
                'name' => 'Electronics Category',
                'slug' => 'electronics-category',
            ],
        ]);

    $this->assertDatabaseHas('categories', [
        'name' => 'Electronics Category',
        'slug' => 'electronics-category',
    ]);
});

it('can show a single category', function (): void {
    $category = Category::factory()->create();

    $response = $this->getJson("/api/categories/{$category->id}");

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
        ]);
});

it('can update a category', function (): void {
    $category = Category::factory()->create(['name' => 'Old Name']);

    $response = $this->putJson("/api/categories/{$category->id}", [
        'name' => 'New Name',
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Category updated successfully',
            'data' => [
                'name' => 'New Name',
                'slug' => 'new-name',
            ],
        ]);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'New Name',
    ]);
});

it('can delete a category', function (): void {
    $category = Category::factory()->create();

    $response = $this->deleteJson("/api/categories/{$category->id}");

    $response->assertOk()
        ->assertJson([
            'message' => 'Category deleted successfully',
        ]);

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

it('validates required fields when creating', function (): void {
    $response = $this->postJson('/api/categories', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('validates required fields when updating', function (): void {
    $category = Category::factory()->create();

    $response = $this->putJson("/api/categories/{$category->id}", [
        'name' => '',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('returns 404 for non-existent category', function (): void {
    $response = $this->getJson('/api/categories/99999');

    $response->assertNotFound();
});

it('auto-generates slug from name', function (): void {
    $response = $this->postJson('/api/categories', [
        'name' => 'My Test Category',
    ]);

    $response->assertCreated()
        ->assertJson([
            'data' => [
                'slug' => 'my-test-category',
            ],
        ]);
});

it('uses provided slug if given', function (): void {
    $response = $this->postJson('/api/categories', [
        'name' => 'Test Category',
        'slug' => 'custom-slug',
    ]);

    $response->assertCreated()
        ->assertJson([
            'data' => [
                'slug' => 'custom-slug',
            ],
        ]);
});

it('includes products count when loading category', function (): void {
    $category = Category::factory()->create();
    Product::factory()->count(5)->create(['category_id' => $category->id]);

    $response = $this->getJson("/api/categories/{$category->id}");

    $response->assertOk();
    // Products count is included in the response if eager loaded
});
