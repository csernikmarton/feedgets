<?php

use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;

test('registration page can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    Mail::fake();

    // Mock the turnstile validation
    $this->mock(Turnstile::class, function ($mock) {
        $mock->shouldReceive('validate')
            ->andReturnUsing(function ($attribute, $value, $fail) {
                // Do nothing - validation passes
            });
    });

    $name = 'Test User';
    $email = 'test@example.com';
    $password = 'PassWord__123%';

    Livewire::test(Register::class)
        ->set('name', $name)
        ->set('email', $email)
        ->set('password', $password)
        ->set('password_confirmation', $password)
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('register');

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => $email,
    ]);

    $user = User::where('email', $email)->first();
    expect($user)->not->toBeNull()
        ->and($user->name)->toBe($name)
        ->and(Hash::check($password, $user->password))->toBeTrue();
});

test('user cannot register with invalid email', function () {
    // Mock the turnstile validation
    $this->mock(Turnstile::class, function ($mock) {
        $mock->shouldReceive('validate')
            ->andReturnUsing(function ($attribute, $value, $fail) {
                // Do nothing - validation passes
            });
    });

    Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'invalid-email')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('register')
        ->assertHasErrors(['email' => 'email']);
});

test('user cannot register with too weak password', function () {
    // Mock the turnstile validation
    $this->mock(Turnstile::class, function ($mock) {
        $mock->shouldReceive('validate')
            ->andReturnUsing(function ($attribute, $value, $fail) {
                // Do nothing - validation passes
            });
    });

    Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('register')
        ->assertHasErrors('password');
});

test('user cannot register with password confirmation not matching', function () {
    // Mock the turnstile validation
    $this->mock(Turnstile::class, function ($mock) {
        $mock->shouldReceive('validate')
            ->andReturnUsing(function ($attribute, $value, $fail) {
                // Do nothing - validation passes
            });
    });

    Livewire::test(Register::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'different-password')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('register')
        ->assertHasErrors(['password' => 'confirmed']);
});
