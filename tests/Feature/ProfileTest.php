<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('pixel art display preference is off by default and can be toggled', function () {
    $user = User::factory()->create();
    expect($user->fresh()->pixel_art)->toBeFalse();

    $this->actingAs($user)
        ->from('/mainMenu')
        ->patch('/profile/preferences', ['pixel_art' => true])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/mainMenu');
    expect($user->fresh()->pixel_art)->toBeTrue();

    $this->actingAs($user)->patch('/profile/preferences', ['pixel_art' => false]);
    expect($user->fresh()->pixel_art)->toBeFalse();
});

test('pixel art preference rejects a non boolean value', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch('/profile/preferences', ['pixel_art' => 'maybe'])
        ->assertSessionHasErrors('pixel_art');
    expect($user->fresh()->pixel_art)->toBeFalse();
});
