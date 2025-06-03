<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

test('login page can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can login with correct credentials', function () {
    // Mock the turnstile validation
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(true);
    });

    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password') // Default password from UserFactory
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('login');

    $this->assertAuthenticated();
    expect(Auth::user()->id)->toBe($user->id);
});

test('users cannot login with incorrect password', function () {
    // Mock the turnstile validation
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(true);
    });

    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('users cannot login with email that does not exist', function () {
    // Mock the turnstile validation
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(true);
    });

    Livewire::test(Login::class)
        ->set('email', 'nonexistent@example.com')
        ->set('password', 'password')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

test('remember me functionality works', function () {
    // Mock the turnstile validation
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(true);
    });

    // Create a mock guard
    $guard = Mockery::mock();
    $guard->shouldReceive('check')->andReturn(true);
    $guard->shouldReceive('guest')->andReturn(false); // User is authenticated, so not a guest
    $guard->shouldReceive('user')->andReturn(User::factory()->create()); // Return a user object if needed

    // Mock the Auth facade to verify remember parameter and provide a guard
    Auth::shouldReceive('attempt')
        ->withArgs(function ($credentials, $remember) {
            return $remember === true;
        })
        ->andReturn(true);

    Auth::shouldReceive('guard')
        ->andReturn($guard);

    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->set('remember', true)
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('login');

    $this->assertAuthenticated();
});
