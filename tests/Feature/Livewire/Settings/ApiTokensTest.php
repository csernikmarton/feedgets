<?php

use App\Livewire\Settings\ApiTokens;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('a user can create an api token and sees it once', function () {
    Livewire::test(ApiTokens::class)
        ->set('tokenName', 'My laptop')
        ->call('createToken')
        ->assertSet('tokenName', '')
        ->assertSet('plainTextToken', fn ($value) => is_string($value) && str_contains($value, '|'));

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $this->user->id,
        'name' => 'My laptop',
    ]);
});

test('the token name is required', function () {
    Livewire::test(ApiTokens::class)
        ->set('tokenName', '')
        ->call('createToken')
        ->assertHasErrors(['tokenName' => 'required']);
});

test('a user can revoke their own token', function () {
    $token = $this->user->createToken('to-delete')->accessToken;

    Livewire::test(ApiTokens::class)
        ->call('deleteToken', $token->id);

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
});

test('a user cannot revoke another user\'s token', function () {
    $otherToken = User::factory()->create()->createToken('theirs')->accessToken;

    Livewire::test(ApiTokens::class)
        ->call('deleteToken', $otherToken->id);

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->id]);
});

test('the component only lists the current user\'s tokens', function () {
    $this->user->createToken('mine');
    User::factory()->create()->createToken('theirs');

    Livewire::test(ApiTokens::class)
        ->assertSee('mine')
        ->assertDontSee('theirs');
});
