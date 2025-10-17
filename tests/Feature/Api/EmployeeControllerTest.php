<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create([
        'role' => 'admin',
    ]);
    Sanctum::actingAs($this->user);
});
it('can show all employees', function () {

    $employees = User::factory()->count(9)->create();

    $response = $this->getJson('/api/employees');

    $response->assertOk();

    $this->assertDatabaseCount('users', 10);
});

it('can show employee', function () {

    $response = $this->getJson('/api/employees/'.$this->user->id);

    $response->assertOk();
});

it('can update employee', function () {
    $employee = User::factory()->create([
        'name' => 'dan',
        'role' => 'cashier',
    ]);

    $response = $this->putJson('/api/employees/'.$employee->id, [
        'name' => 'Test Employee',
    ]);

    $employee->refresh();

    $response->assertOk()
        ->assertJson([
            'data' => [
                'name' => 'Test Employee',
            ],
        ]);

    expect($employee->name)->toBe('Test Employee');
});

it('can create employee without employee_id and auto-generates one', function () {
    $response = $this->postJson('/api/employees', [
        'name' => 'New Employee',
        'email' => 'newemployee@test.com',
        'password' => 'password123',
        'role' => 'cashier',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'employee_id',
                'role',
            ],
        ]);

    $employee = User::where('email', 'newemployee@test.com')->first();
    expect($employee->employee_id)->not->toBeNull();
    expect($employee->employee_id)->toMatch('/^EMP-\d{4}-\d{4}$/');
});
