<?php

use App\Livewire\Settings\DeleteUserForm;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('delete user form can be rendered', function () {
    Livewire::test(DeleteUserForm::class)
        ->assertSuccessful();
});

test('user can confirm deletion', function () {
    Livewire::test(DeleteUserForm::class)
        ->call('confirmUserDeletion')
        ->assertSet('confirmingUserDeletion', true);
});

test('user can delete account with correct password', function () {
    Livewire::test(DeleteUserForm::class)
        ->set('password', 'password') // Default password from UserFactory
        ->call('deleteUser')
        ->assertRedirect('/');

    $this->assertDatabaseMissing('users', [
        'id' => $this->user->id,
    ]);
});

test('user cannot delete account with incorrect password', function () {
    Livewire::test(DeleteUserForm::class)
        ->set('password', 'wrong-password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
    ]);
});

test('user cannot delete account without password', function () {
    Livewire::test(DeleteUserForm::class)
        ->set('password', '')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
    ]);
});
