<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register with valid data', function (): void {
    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = $this->post('/register', $userData);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Test User');
});

test('user can login with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
});

test('user cannot login with invalid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest();
});

test('registration requires all fields', function (): void {
    $response = $this->post('/register', []);

    $response->assertSessionHasErrors(['name', 'email', 'password']);
    $this->assertGuest();
});

test('registration requires password confirmation', function (): void {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'differentpassword',
    ]);

    $response->assertSessionHasErrors(['password']);
    $this->assertGuest();
});

test('user can logout', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
});
