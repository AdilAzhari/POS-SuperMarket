<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\Sale;
use App\Models\SaleItem;
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
    $this->product = Product::factory()->create(['price' => 100.00]);

    // Attach product to store with stock
    $this->product->stores()->attach($this->store->id, [
        'stock' => 50,
        'low_stock_threshold' => 10,
    ]);

    // Create a completed sale with items
    $this->sale = Sale::factory()->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'cashier_id' => $this->user->id,
        'status' => 'completed',
        'subtotal' => 200.00,
        'tax' => 12.00,
        'total' => 212.00,
        'payment_method' => 'cash',
    ]);

    $this->saleItem = SaleItem::factory()->create([
        'sale_id' => $this->sale->id,
        'product_id' => $this->product->id,
        'product_name' => $this->product->name,
        'sku' => $this->product->sku,
        'price' => 100.00,
        'quantity' => 2,
        'discount' => 0,
        'tax' => 12.00,
        'line_total' => 200.00,
    ]);

    // Update product stock after sale (simulate sale reducing stock)
    $this->product->stores()->updateExistingPivot($this->store->id, [
        'stock' => 48, // 50 - 2
    ]);
});

it('can list returns', function (): void {
    ProductReturn::factory()->count(3)->create([
        'store_id' => $this->store->id,
        'processed_by' => $this->user->id,
    ]);

    $response = $this->getJson('/api/returns');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'code',
                    'sale_id',
                    'reason',
                    'refund_method',
                    'total_refund',
                    'status',
                ],
            ],
            'pagination' => [
                'current_page',
                'per_page',
                'total',
            ],
        ]);
});

it('can show a specific return', function (): void {
    $return = ProductReturn::factory()->create([
        'sale_id' => $this->sale->id,
        'store_id' => $this->store->id,
        'processed_by' => $this->user->id,
    ]);

    $response = $this->getJson("/api/returns/{$return->id}");

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $return->id,
                'code' => $return->code,
                'sale_id' => $this->sale->id,
            ],
        ]);
});

it('can process a product return successfully', function (): void {
    $returnData = [
        'sale_id' => $this->sale->id,
        'reason' => 'defective',
        'refund_method' => 'cash',
        'notes' => 'Product was damaged',
        'processed_by' => $this->user->id,
        'items' => [
            [
                'sale_item_id' => $this->saleItem->id,
                'quantity' => 1,
                'condition_notes' => 'Box damaged',
            ],
        ],
    ];

    $response = $this->postJson('/api/returns', $returnData);

    $response->assertCreated()
        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'code',
                'sale_id',
                'total_refund',
                'items',
            ],
        ]);

    // Verify return was created
    $this->assertDatabaseHas('returns', [
        'sale_id' => $this->sale->id,
        'reason' => 'defective',
        'refund_method' => 'cash',
    ]);

    // Verify return item was created
    $this->assertDatabaseHas('return_items', [
        'sale_item_id' => $this->saleItem->id,
        'product_id' => $this->product->id,
        'quantity_returned' => 1,
    ]);

    // Verify inventory was restored (48 + 1 = 49)
    $updatedStock = $this->product->stores()->where('stores.id', $this->store->id)->first()->pivot->stock;
    expect($updatedStock)->toBe(49);

    // Verify stock movement was created
    $this->assertDatabaseHas('stock_movements', [
        'product_id' => $this->product->id,
        'store_id' => $this->store->id,
        'type' => 'addition',
        'quantity' => 1,
        'reason' => 'return',
    ]);

    // Verify refund payment was created
    $this->assertDatabaseHas('payments', [
        'sale_id' => $this->sale->id,
        'status' => 'completed',
    ]);
});

it('validates return data correctly', function (): void {
    $response = $this->postJson('/api/returns', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['sale_id', 'reason', 'refund_method', 'items']);
});

it('cannot return more items than purchased', function (): void {
    $returnData = [
        'sale_id' => $this->sale->id,
        'reason' => 'defective',
        'refund_method' => 'cash',
        'processed_by' => $this->user->id,
        'items' => [
            [
                'sale_item_id' => $this->saleItem->id,
                'quantity' => 5, // More than the 2 purchased
            ],
        ],
    ];

    $response = $this->postJson('/api/returns', $returnData);

    $response->assertStatus(500); // Will throw exception
});

it('updates sale status to refunded when all items returned', function (): void {
    $returnData = [
        'sale_id' => $this->sale->id,
        'reason' => 'customer_change_mind',
        'refund_method' => 'original_payment',
        'processed_by' => $this->user->id,
        'items' => [
            [
                'sale_item_id' => $this->saleItem->id,
                'quantity' => 2, // Return all items
            ],
        ],
    ];

    $response = $this->postJson('/api/returns', $returnData);

    $response->assertCreated();

    // Verify sale status was updated
    $this->sale->refresh();
    expect($this->sale->status->value)->toBe('refunded');
});

it('updates sale status to partially_refunded when some items returned', function (): void {
    $returnData = [
        'sale_id' => $this->sale->id,
        'reason' => 'defective',
        'refund_method' => 'cash',
        'processed_by' => $this->user->id,
        'items' => [
            [
                'sale_item_id' => $this->saleItem->id,
                'quantity' => 1, // Return only 1 of 2 items
            ],
        ],
    ];

    $response = $this->postJson('/api/returns', $returnData);

    $response->assertCreated();

    // Verify sale status was updated
    $this->sale->refresh();
    expect($this->sale->status->value)->toBe('partially_refunded');
});

it('can update return status', function (): void {
    $return = ProductReturn::factory()->create([
        'sale_id' => $this->sale->id,
        'store_id' => $this->store->id,
        'processed_by' => $this->user->id,
        'status' => 'pending',
    ]);

    $response = $this->putJson("/api/returns/{$return->id}", [
        'status' => 'completed',
        'notes' => 'Processed successfully',
    ]);

    $response->assertOk();

    $return->refresh();
    expect($return->status)->toBe('completed');
    expect($return->notes)->toBe('Processed successfully');
});

it('cannot delete processed returns', function (): void {
    $return = ProductReturn::factory()->create([
        'sale_id' => $this->sale->id,
        'store_id' => $this->store->id,
        'processed_by' => $this->user->id,
        'status' => 'completed',
    ]);

    $response = $this->deleteJson("/api/returns/{$return->id}");

    $response->assertUnprocessable()
        ->assertJson([
            'message' => 'Cannot delete returns that have been processed',
        ]);
});

it('can delete pending returns', function (): void {
    $return = ProductReturn::factory()->create([
        'sale_id' => $this->sale->id,
        'store_id' => $this->store->id,
        'processed_by' => $this->user->id,
        'status' => 'pending',
    ]);

    $response = $this->deleteJson("/api/returns/{$return->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('returns', ['id' => $return->id]);
});
