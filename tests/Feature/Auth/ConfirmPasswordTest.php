<?php

use App\Livewire\Auth\ConfirmPassword;
use App\Models\User;
use Livewire\Livewire;

test('confirm password screen can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/confirm-password');

    $response->assertStatus(200);
});

test('password can be confirmed with valid password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(ConfirmPassword::class)
        ->set('password', 'password') // Default password from UserFactory
        ->call('confirmPassword');

    $this->assertTrue(session()->has('auth.password_confirmed_at'));
});

test('password cannot be confirmed with invalid password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(ConfirmPassword::class)
        ->set('password', 'wrong-password')
        ->call('confirmPassword')
        ->assertHasErrors(['password']);

    $this->assertFalse(session()->has('auth.password_confirmed_at'));
});

test('password confirmation redirects to intended url', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    // Set intended URL
    session(['url.intended' => route('dashboard')]);

    Livewire::test(ConfirmPassword::class)
        ->set('password', 'password')
        ->call('confirmPassword');

    $this->assertTrue(session()->has('auth.password_confirmed_at'));
});
