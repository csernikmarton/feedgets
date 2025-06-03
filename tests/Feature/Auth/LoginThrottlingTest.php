<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

test('login attempts are throttled after too many failed attempts', function () {
    // Mock the turnstile validation
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(true);
    });

    Event::fake([Lockout::class]);

    $user = User::factory()->create();

    // Attempt login with wrong password multiple times
    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->set('turnstileResponse', 'test_turnstile_response')
            ->call('login')
            ->assertHasErrors(['email']);
    }

    // Next attempt should trigger lockout event
    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('login');

    Event::assertDispatched(Lockout::class);

    // Clean up rate limiter for the test user
    $throttleKey = strtolower($user->email).'|'.$this->app['request']->ip();
    RateLimiter::clear($throttleKey);
});

test('rate limiter is cleared after successful login', function () {
    // Mock the turnstile validation
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(true);
    });

    $user = User::factory()->create();
    $throttleKey = strtolower($user->email).'|'.$this->app['request']->ip();

    // Add some failed attempts to the rate limiter
    RateLimiter::increment($throttleKey, 60, 3);
    expect(RateLimiter::attempts($throttleKey))->toBe(3);

    // Successful login should clear the rate limiter
    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('login');

    expect(RateLimiter::attempts($throttleKey))->toBe(0);
});
