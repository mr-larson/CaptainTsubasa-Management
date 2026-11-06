<?php

use App\Providers\RouteServiceProvider;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(RouteServiceProvider::HOME);
});

test('a mismatched confirmation is reported on the confirmation field', function () {
    $response = $this->from('/register')->post('/register', [
        'name' => 'Test User',
        'email' => 'mismatch@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
    ]);

    $response->assertSessionHasErrors('password_confirmation');
    $response->assertSessionDoesntHaveErrors('password');
    $this->assertGuest();
});
