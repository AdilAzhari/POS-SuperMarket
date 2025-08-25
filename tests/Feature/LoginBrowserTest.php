<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login page loads correctly', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Auth/Login')
        ->has('canResetPassword')
    );
});

test('login page has csrf token', function () {
    $response = $this->get('/login');
    
    $response->assertStatus(200);
    
    // Check that CSRF token meta tag is present
    $content = $response->getContent();
    expect($content)->toContain('name="csrf-token"');
    expect($content)->toContain('content="');
});

test('can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
});

test('cannot login with invalid credentials', function () {
    User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('csrf token is required for login', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // Attempt login without CSRF token
    $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

    // Should succeed when CSRF middleware is disabled
    $response->assertRedirect('/dashboard');
    
    // Now test with CSRF middleware enabled (should fail without token)
    $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertStatus(419); // Page Expired / CSRF Token Mismatch
});

test('login form preserves email on validation error', function () {
    $response = $this->post('/login', [
        'email' => 'invalid-email',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $response->assertSessionHas('email', 'invalid-email');
});

test('register page loads correctly', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Auth/Register')
    );
});

test('can register new user', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
    
    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
});

test('registration requires csrf token', function () {
    // Test without CSRF middleware first
    $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
        ->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $response->assertRedirect('/dashboard');
    
    // Now test with CSRF middleware (should fail)
    $response = $this->post('/register', [
        'name' => 'Test User 2',
        'email' => 'test2@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(419); // Page Expired / CSRF Token Mismatch
});
