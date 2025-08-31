<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);

    $this->store = Store::factory()->create();
    $this->customer = Customer::factory()->create();
    $this->product = Product::factory()->create();

    // Attach product to store with stock
    $this->product->stores()->attach($this->store->id, [
        'stock' => 100,
        'low_stock_threshold' => 10,
    ]);
});

it('can list sales', function (): void {
    Sale::factory()->count(3)->create();

    $response = $this->getJson('/api/sales');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'code',
                    'store_id',
                    'cashier_id',
                    'items_count',
                    'subtotal',
                    'total',
                    'payment_method',
                    'status',
                ],
            ],
            'current_page',
            'per_page',
            'total',
        ]);
});

it('can show a specific sale', function (): void {
    $sale = Sale::factory()->create();

    $response = $this->getJson("/api/sales/{$sale->id}");

    $response->assertOk()
        ->assertJson([
            'id' => $sale->id,
            'code' => $sale->code,
        ]);
});

it('can create a sale', function (): void {
    $saleData = [
        'store_id' => $this->store->id,
        'cashier_id' => $this->user->id,
        'customer_id' => $this->customer->id,
        'payment_method' => 'cash',
        'discount' => 0,
        'tax' => 1.60,
        'items' => [
            [
                'product_id' => $this->product->id,
                'price' => 10.00,
                'quantity' => 2,
                'discount' => 0,
                'tax' => 0,
            ],
        ],
    ];

    $response = $this->postJson('/api/sales', $saleData);

    $response->assertCreated()
        ->assertJsonStructure([
            'id',
            'code',
            'store_id',
            'cashier_id',
            'customer_id',
            'items_count',
            'subtotal',
            'total',
            'payment_method',
        ]);

    $this->assertDatabaseHas('sales', [
        'store_id' => $this->store->id,
        'cashier_id' => $this->user->id,
        'customer_id' => $this->customer->id,
        'payment_method' => 'cash',
    ]);
});

it('validates required fields when creating sale', function (): void {
    $response = $this->postJson('/api/sales', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['store_id', 'cashier_id', 'payment_method', 'items']);
});

it('returns error when insufficient stock', function (): void {
    // Set low stock
    $this->product->stores()->updateExistingPivot($this->store->id, ['stock' => 1]);

    $saleData = [
        'store_id' => $this->store->id,
        'cashier_id' => $this->user->id,
        'payment_method' => 'cash',
        'items' => [
            [
                'product_id' => $this->product->id,
                'price' => 10.00,
                'quantity' => 5, // More than available
                'discount' => 0,
                'tax' => 0,
            ],
        ],
    ];

    $response = $this->postJson('/api/sales', $saleData);

    $response->assertUnprocessable()
        ->assertJson([
            'error' => 'Insufficient Stock',
        ]);
});

it('prevents sale updates', function (): void {
    $sale = Sale::factory()->create();

    $response = $this->putJson("/api/sales/{$sale->id}", [
        'status' => 'voided',
    ]);

    $response->assertUnprocessable()
        ->assertJson([
            'message' => 'Sales cannot be modified after creation',
        ]);
});

it('prevents sale deletion', function (): void {
    $sale = Sale::factory()->create();

    $response = $this->deleteJson("/api/sales/{$sale->id}");

    $response->assertUnprocessable()
        ->assertJson([
            'message' => 'Sales cannot be deleted. Use void endpoint instead',
        ]);
});

it('decrements stock after sale', function (): void {
    $initialStock = $this->product->stores()->where('stores.id', $this->store->id)->first()->pivot->stock;

    $saleData = [
        'store_id' => $this->store->id,
        'cashier_id' => $this->user->id,
        'payment_method' => 'cash',
        'items' => [
            [
                'product_id' => $this->product->id,
                'price' => 10.00,
                'quantity' => 3,
                'discount' => 0,
                'tax' => 0,
            ],
        ],
    ];

    $this->postJson('/api/sales', $saleData);

    $this->product->refresh();
    $newStock = $this->product->stores()->where('stores.id', $this->store->id)->first()->pivot->stock;

    expect($newStock)->toBe($initialStock - 3);
});

it('updates customer statistics after sale', function (): void {
    $initialPurchases = $this->customer->total_purchases;
    $initialSpent = $this->customer->total_spent;

    $saleData = [
        'store_id' => $this->store->id,
        'cashier_id' => $this->user->id,
        'customer_id' => $this->customer->id,
        'payment_method' => 'cash',
        'items' => [
            [
                'product_id' => $this->product->id,
                'price' => 15.00,
                'quantity' => 1,
                'discount' => 0,
                'tax' => 0,
            ],
        ],
    ];

    $this->postJson('/api/sales', $saleData);

    $this->customer->refresh();

    expect($this->customer->total_purchases)->toBe($initialPurchases + 1)
        ->and((float) $this->customer->total_spent)->toBe($initialSpent + 15.0);
});
