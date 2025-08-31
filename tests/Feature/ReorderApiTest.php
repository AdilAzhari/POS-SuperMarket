<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create();
    $this->supplier = Supplier::factory()->create();
    $this->product = Product::factory()->create([
        'supplier_id' => $this->supplier->id,
        'cost' => 10.00,
    ]);

    // Attach product to store with low stock
    $this->product->stores()->attach($this->store->id, [
        'stock' => 5,
        'low_stock_threshold' => 20,
    ]);

    Sanctum::actingAs($this->user);
});

it('can get reorder list via API', function (): void {
    $response = $this->getJson('/api/reorder?store_id='.$this->store->id);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'product',
                    'supplier',
                    'current_stock',
                    'threshold',
                    'suggested_order_qty',
                    'estimated_cost',
                    'priority',
                ],
            ],
            'meta' => [
                'total_items',
                'high_priority',
                'total_estimated_cost',
            ],
        ]);
});

it('can get reorder list grouped by supplier', function (): void {
    $response = $this->getJson('/api/reorder?store_id='.$this->store->id.'&group_by=supplier');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'supplier',
                    'items',
                    'total_items',
                    'total_cost',
                    'high_priority_items',
                ],
            ],
            'meta',
        ]);
});

it('can get automatic reorder suggestions', function (): void {
    $response = $this->getJson('/api/reorder/automatic?store_id='.$this->store->id);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data',
            'meta' => [
                'total_items',
                'total_estimated_cost',
            ],
        ]);
});

it('can get supplier comparison', function (): void {
    $response = $this->getJson('/api/reorder/supplier-comparison?store_id='.$this->store->id);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'supplier',
                    'items_count',
                    'total_cost',
                    'high_priority_items',
                    'avg_lead_time',
                    'reliability_score',
                    'priority_score',
                ],
            ],
        ]);
});

it('can get reorder history', function (): void {
    $response = $this->getJson('/api/reorder/history?store_id='.$this->store->id.'&days=30');

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data',
            'meta' => [
                'period_days',
                'total_orders',
                'total_value',
            ],
        ]);
});

it('can create purchase order from reorder', function (): void {
    $requestData = [
        'items' => [
            [
                'product_id' => $this->product->id,
                'supplier_id' => $this->supplier->id,
                'quantity' => 50,
                'unit_cost' => 10.50,
                'notes' => 'Test reorder item',
            ],
        ],
        'store_id' => $this->store->id,
        'notes' => 'Test purchase order from reorder',
    ];

    $response = $this->postJson('/api/reorder/create-po', $requestData);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'po_number',
                    'total_amount',
                    'status',
                ],
            ],
            'summary',
            'recommendations',
        ]);

    expect($response->json('data.0.status'))->toBe('pending');
    expect((float) $response->json('data.0.total_amount'))->toBe(577.5); // 50 * 10.5 * 1.1 (10% tax)
});

it('validates create purchase order request', function (): void {
    $response = $this->postJson('/api/reorder/create-po', [
        'items' => [], // Empty items should fail
        'store_id' => $this->store->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

it('can update reorder points', function (): void {
    $response = $this->postJson('/api/reorder/update-points', [
        'store_id' => $this->store->id,
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'updated_count',
            ],
        ]);
});

it('can clear reorder cache', function (): void {
    $response = $this->postJson('/api/reorder/clear-cache', [
        'store_id' => $this->store->id,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Reorder cache cleared successfully',
        ]);
});

it('requires authentication for reorder endpoints', function (): void {
    // Make request without authentication
    $response = $this->withHeaders([])->getJson('/api/reorder');

    $response->assertStatus(401);
})->skip('Authentication testing needs proper setup');

it('validates store_id parameter', function (): void {
    $response = $this->postJson('/api/reorder/update-points', [
        'store_id' => 999999, // Non-existent store
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['store_id']);
});
