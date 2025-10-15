<?php

declare(strict_types=1);

use App\Enums\CustomerStatus;
use App\Enums\LoyaltyTier;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('can list all customers', function (): void {

    Customer::factory()->count(3)->create();

    $response = $this->getJson('/api/customers');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'name',
                    'phone',
                    'email',
                    'address',
                    'total_purchases',
                    'total_spent',
                    'last_purchase_at',
                    'status',
                    'loyalty_points',
                    'loyalty_tier',
                    'birthday',
                    'marketing_consent',
                ],
            ],
        ]);

    $this->assertDatabaseCount('customers', 3);
});

it('can fetch all customers when all parameter is true', function (): void {
    Customer::factory()->count(50)->create();

    $response = $this->getJson('/api/customers?all=true');

    $response->assertOk();

    $data = $response->json('data');
    expect($data)->toHaveCount(50);
});

it('can create new customer', function (): void {
    $customer = [
        'name' => 'Electronics Category',
        'phone' => '0123456789',
        'email' => 'hana@gmail.com',
        'address' => 'home',
        'total_purchases' => 2,
        'total_spent' => 22,
        'last_purchase_at' => now(),
        'status' => CustomerStatus::ACTIVE,
        'loyalty_points' => 2,
        'loyalty_tier' => LoyaltyTier::GOLD,
        'birthday' => '1998',
        'marketing_consent' => true,
    ];
    $response = $this->postJson('/api/customers', $customer);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'name',
                'phone',
                'email',
                'address',
            ],
        ])
        ->assertSee('Electronics Category');

    $this->assertDatabaseCount('customers', 1);

    expect($customer['name'])->toBe('Electronics Category')
        ->and($customer['phone'])->toBe('0123456789')
        ->and($customer['email'])->toBe('hana@gmail.com');
});

it('can show customer', function (): void {

    $customer = Customer::factory()->create([
        'name' => 'doffy',
        'email' => 'doffy@gmail.com',
        'phone' => '0123456789',
    ]);

    $response = $this->getJson('/api/customers/'.$customer->id);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'name',
                'phone',
                'email',
            ],
        ]);

});

it('can update customer', function (): void {
    $customer = Customer::factory()->create([
        'name' => 'doffy',
        'email' => 'doffy@gmail.com',
        'phone' => '0123456789',
    ]);

    $this->putJson('/api/customers/'.$customer->id, [
        'name' => 'Electronics Category',
    ])->assertOk()
        ->assertJsonStructure([
            'data' => [
                'name',
                'phone',
                'email',
            ],
        ]);

    $customer->refresh();

    expect($customer->name)->toBe('Electronics Category')
        ->and($customer->phone)->toBe('0123456789');
});

it('can delete customer', function (): void {
    $customer = Customer::factory()->create([
        'name' => 'doffy',
        'phone' => '0123456789',
        'email' => 'dana@gmai.com',
    ]);

    $response = $this->deleteJson('/api/customers/'.$customer->id);

    $response->assertOk();

    expect($customer->count())->toBe(0);
});
