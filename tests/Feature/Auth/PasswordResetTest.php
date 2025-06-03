<?php

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

test('forgot password page can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password page can be rendered', function () {
    $response = $this->get('/reset-password/fake-token');

    $response->assertStatus(200);
});

test('users can request password reset link', function () {
    // Mock the turnstile validation
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(true);
    });

    Notification::fake();

    $user = User::factory()->create();

    Livewire::test(ForgotPassword::class)
        ->set('email', $user->email)
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('sendPasswordResetLink');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
});

test('users receive success message even for non-existent email', function () {
    // Mock the turnstile validation
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(true);
    });

    Notification::fake();

    Livewire::test(ForgotPassword::class)
        ->set('email', 'nonexistent@example.com')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('sendPasswordResetLink')
        ->assertHasNoErrors();

    Notification::assertNothingSent();
});

test('users can reset password with valid token', function () {
    // Mock the turnstile validation
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(true);
    });

    $user = User::factory()->create();

    $token = Password::createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', $user->email)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('resetPassword');

    $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
});

test('users cannot reset password with invalid token', function () {
    // Mock the turnstile validation
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(true);
    });

    $user = User::factory()->create();
    $originalPassword = $user->password;

    Livewire::test(ResetPassword::class, ['token' => 'invalid-token'])
        ->set('email', $user->email)
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('resetPassword')
        ->assertHasErrors(['email']);

    expect($user->fresh()->password)->toBe($originalPassword);
});

test('users cannot reset password with invalid email', function () {
    // Mock the turnstile validation
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(true);
    });

    $user = User::factory()->create();
    $token = Password::createToken($user);

    Livewire::test(ResetPassword::class, ['token' => $token])
        ->set('email', 'wrong@example.com')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('resetPassword')
        ->assertHasErrors(['email']);
});

test('refresh-turnstile event is dispatched when validation fails (ForgotPassword)', function () {
    // Mock the Turnstile validation to fail
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(false);
        $mock->shouldReceive('message')
            ->andReturn('Turnstile validation failed.');
    });

    Livewire::test(ForgotPassword::class)
        ->set('email', 'invalid-email')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('sendPasswordResetLink')
        ->assertDispatched('refresh-turnstile');
});

test('refresh-turnstile event is dispatched when validation fails (ResetPassword)', function () {
    // Mock the Turnstile validation to fail
    $this->mock(\RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile::class, function ($mock) {
        $mock->shouldReceive('passes')
            ->andReturn(false);
        $mock->shouldReceive('message')
            ->andReturn('Turnstile validation failed.');
    });

    Livewire::test(ResetPassword::class, ['token' => 'invalid-token'])
        ->set('email', 'invalid-email')
        ->set('turnstileResponse', 'test_turnstile_response')
        ->call('resetPassword')
        ->assertDispatched('refresh-turnstile');
});
