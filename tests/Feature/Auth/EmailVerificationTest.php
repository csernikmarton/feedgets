<?php

use App\Livewire\Auth\VerifyEmail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    $response = $this->get('/verify-email');

    $response->assertStatus(200);
});

test('email verification link can be requested', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    Notification::fake();

    Livewire::test(VerifyEmail::class)
        ->call('sendVerification');

    Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
});

test('email can be verified', function () {
    Event::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->get($verificationUrl);

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard').'?verified=1');
});

test('email verification redirects if already verified', function () {
    $user = User::factory()->create(); // Already verified by default

    $this->actingAs($user);

    Livewire::test(VerifyEmail::class)
        ->call('sendVerification');

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->get($verificationUrl);

    $response->assertRedirect(route('dashboard').'?verified=1');
});

test('email verification fails with invalid hash', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => 'invalid-hash']
    );

    $response = $this->get($verificationUrl);

    $response->assertStatus(403);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('logout method redirects to home page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    // Test the logout method redirects to home page
    Livewire::test(VerifyEmail::class)
        ->call('logout')
        ->assertRedirect('/');
});
