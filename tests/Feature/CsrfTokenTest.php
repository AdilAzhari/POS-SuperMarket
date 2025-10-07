<?php

declare(strict_types=1);

uses();

test('csrf token is generated on web pages', function (): void {
    $response = $this->get('/login');

    $response->assertStatus(200);

    // Extract CSRF token from meta tag
    $content = $response->getContent();
    expect($content)->toContain('name="csrf-token"');

    // Parse the token from meta tag
    preg_match('/name="csrf-token"\s+content="([^"]+)"/', $content, $matches);

    if (isset($matches[1])) {
        $token = $matches[1];
        expect(mb_strlen($token))->toBeGreaterThan(0);
        expect($token)->not->toBeNull();
    } else {
        throw new Exception('CSRF token not found in meta tag');
    }
});

test('post request without csrf token should fail', function (): void {
    // Get login page to start session
    $this->get('/login');

    // Try to post without CSRF token
    $response = $this->post('/login', [
        'email' => 'test@test.com',
        'password' => 'password',
    ], [
        'X-Requested-With' => 'XMLHttpRequest', // Simulate AJAX request
    ]);

    // Should get 419 status (CSRF token mismatch)
    $response->assertStatus(419);
});

test('post request with valid csrf token should work', function (): void {
    // Get login page first to establish session and get token
    $response = $this->get('/login');

    // Extract CSRF token from the response
    $content = $response->getContent();
    preg_match('/name="csrf-token"\s+content="([^"]+)"/', $content, $matches);

    if (! isset($matches[1])) {
        throw new Exception('CSRF token not found');
    }

    $csrfToken = $matches[1];

    // Now make a POST request with the CSRF token
    $response = $this->post('/login', [
        '_token' => $csrfToken,
        'email' => 'test@test.com',
        'password' => 'password',
    ]);

    // Should not be 419 (we expect validation error or redirect, but not CSRF error)
    $response->assertStatus(302); // Should redirect or show validation errors
});
