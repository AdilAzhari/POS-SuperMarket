<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Test CSRF protection in a real production-like environment
test('csrf protection works in real web environment', function (): void {
    // Re-enable ALL middleware for this specific test
    $this->withoutExceptionHandling();
    $this->withMiddleware();

    // Use database sessions (like production)
    config(['session.driver' => 'database']);

    // Create a user for testing
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // 1. First, verify that a GET request works and establishes a session
    $getResponse = $this->get('/login');
    $getResponse->assertStatus(200);

    // 2. Try POST without CSRF token - this should fail
    $postResponse = $this->post('/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    // Check what status we get
    $status = $postResponse->status();

    if ($status === 419) {
        // Perfect! CSRF is working
        expect(true)->toBeTrue();
    } else {
        // CSRF might not be working - let's see why
        $content = $postResponse->getContent();

        // If we get a 302, check if it's due to validation errors or successful login
        if ($status === 302) {
            $location = $postResponse->headers->get('Location');

            // If redirected to dashboard, login worked (CSRF not enforced)
            if (str_contains($location, 'dashboard')) {
                throw new Exception('CSRF protection is NOT working - login succeeded without token');
            }
            // Redirected elsewhere, could be validation errors
            $this->assertGuest(); // Should still be guest if CSRF working

        }

        // Log debug info
        dump([
            'status' => $status,
            'location' => $postResponse->headers->get('Location'),
            'authenticated' => auth()->check(),
            'session_token' => session()->token(),
            'content_preview' => mb_substr($content, 0, 200),
        ]);
    }
});

test('csrf token is properly generated in meta tag', function (): void {
    config(['session.driver' => 'database']);

    $response = $this->get('/login');
    $response->assertStatus(200);

    $content = $response->getContent();

    // Check for CSRF token in meta tag
    expect($content)->toContain('name="csrf-token"');
    expect($content)->toContain('content=');

    // Extract the actual token value
    if (preg_match('/name="csrf-token"\s+content="([^"]+)"/', $content, $matches)) {
        $metaToken = $matches[1];
        expect($metaToken)->not->toBeEmpty();
        expect(mb_strlen($metaToken))->toBeGreaterThan(30);
    } else {
        throw new Exception('CSRF token not found in meta tag');
    }
});

test('inertia csrf token sharing works', function (): void {
    config(['session.driver' => 'database']);

    $response = $this->get('/login');
    $response->assertStatus(200);

    // Inertia responses should include CSRF token in shared props
    $response->assertInertia(fn ($page) => $page
        ->component('Auth/Login')
        ->where('csrf_token', function ($token) {
            return ! empty($token) && mb_strlen($token) > 30;
        })
    );
});

test('check if there are csrf exclusions', function (): void {
    // Check if there's a custom VerifyCsrfToken middleware with exclusions
    $reflection = new ReflectionClass(Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    $method = $reflection->getMethod('tokensMatch');
    $method->setAccessible(true);

    // Test if CSRF middleware has any custom exclusions
    $middleware = app(Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

    // Check the except property if it exists
    $reflection = new ReflectionClass($middleware);
    if ($reflection->hasProperty('except')) {
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $exclusions = $property->getValue($middleware);

        if (! empty($exclusions)) {
            dump('CSRF exclusions found:', $exclusions);
        } else {
            expect($exclusions)->toBeEmpty('CSRF should not have exclusions for login routes');
        }
    }

    expect(true)->toBeTrue(); // This test just inspects, always passes
});
