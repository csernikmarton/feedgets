<?php

use App\Livewire\Settings\UserProfile;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('profile page can be rendered', function () {
    $response = $this->get('/profile');
    $response->assertStatus(200);
});

test('component mounts with correct user data', function () {
    Livewire::test(UserProfile::class)
        ->assertSet('name', $this->user->name)
        ->assertSet('email', $this->user->email);
});

test('user can update profile information', function () {
    $newName = 'New Name';
    $newEmail = 'new-email@example.com';

    Livewire::test(UserProfile::class)
        ->set('name', $newName)
        ->set('email', $newEmail)
        ->call('updateProfile')
        ->assertHasNoErrors()
        ->assertDispatched('saved');

    $this->user->refresh();

    expect($this->user->name)->toBe($newName);
    expect($this->user->email)->toBe($newEmail);
    expect($this->user->email_verified_at)->toBeNull();
});

test('user cannot update profile with invalid data', function () {
    Livewire::test(UserProfile::class)
        ->set('name', '')
        ->set('email', 'not-an-email')
        ->call('updateProfile')
        ->assertHasErrors(['name', 'email']);
});

test('user can update password', function () {
    $newPassword = 'new-password';

    Livewire::test(UserProfile::class)
        ->set('current_password', 'password') // Default password from UserFactory
        ->set('password', $newPassword)
        ->set('password_confirmation', $newPassword)
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertDispatched('password-saved');

    $this->user->refresh();

    expect(Hash::check($newPassword, $this->user->password))->toBeTrue();
});

test('user cannot update password with incorrect current password', function () {
    Livewire::test(UserProfile::class)
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);
});

test('user cannot update password with invalid new password', function () {
    Livewire::test(UserProfile::class)
        ->set('current_password', 'password')
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('updatePassword')
        ->assertHasErrors(['password']);
});

test('user cannot update password with mismatched confirmation', function () {
    Livewire::test(UserProfile::class)
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'different-password')
        ->call('updatePassword')
        ->assertHasErrors(['password']);
});

test('user can resend verification email', function () {
    // Make sure the user is not verified
    $this->user->email_verified_at = null;
    $this->user->save();

    // Mock Auth::user() to return our test user
    $user = Mockery::mock(User::class);
    $user->shouldReceive('hasVerifiedEmail')->once()->andReturn(false);
    $user->shouldReceive('sendEmailVerificationNotification')->once();
    $user->shouldReceive('getAttribute')->with('name')->andReturn($this->user->name);
    $user->shouldReceive('getAttribute')->with('email')->andReturn($this->user->email);
    $user->shouldReceive('getAttribute')->with('email_verified_at')->andReturn(null);

    Auth::shouldReceive('user')->andReturn($user);

    $component = Livewire::test(UserProfile::class);
    $component->call('resendVerificationEmail')
        ->assertHasNoErrors();
    expect($component)->assertFlashMessageHas('profile_message');
});

test('verified user cannot resend verification email', function () {
    // Make sure the user is verified
    $this->user->email_verified_at = now();
    $this->user->save();

    // Mock Auth::user() to return our test user
    $user = Mockery::mock(User::class);
    $user->shouldReceive('hasVerifiedEmail')->once()->andReturn(true);
    $user->shouldReceive('sendEmailVerificationNotification')->never();
    $user->shouldReceive('getAttribute')->with('name')->andReturn($this->user->name);
    $user->shouldReceive('getAttribute')->with('email')->andReturn($this->user->email);
    $user->shouldReceive('getAttribute')->with('email_verified_at')->andReturn($this->user->email_verified_at);

    Auth::shouldReceive('user')->andReturn($user);

    Livewire::test(UserProfile::class)
        ->call('resendVerificationEmail')
        ->assertHasNoErrors();
});
