<?php

use App\Livewire\Actions\Logout;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('logout action logs out the user', function () {
    // Verify user is logged in
    expect(Auth::check())->toBeTrue();

    // Mock Session to verify it's invalidated and token is regenerated
    Session::shouldReceive('invalidate')->once();
    Session::shouldReceive('regenerateToken')->once();

    // Call the logout action
    $logout = new Logout;
    $response = $logout();

    // Verify user is logged out
    expect(Auth::check())->toBeFalse();

    // Verify redirect
    expect($response->getTargetUrl())->toBe(config('app.url'));
});

test('logout action can be used as a callable', function () {
    // Verify user is logged in
    expect(Auth::check())->toBeTrue();

    // Mock Session to verify it's invalidated and token is regenerated
    Session::shouldReceive('invalidate')->once();
    Session::shouldReceive('regenerateToken')->once();

    // Use the logout action as a callable
    $logout = new Logout;
    $callable = $logout(...);
    $response = $callable($this->user);

    // Verify user is logged out
    expect(Auth::check())->toBeFalse();

    // Verify redirect
    expect($response->getTargetUrl())->toBe(config('app.url'));
});
