<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('a user can register and receives a token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password1234',
        'password_confirmation' => 'password1234',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
        ->assertJsonPath('user.email', 'ada@example.com');

    $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
});

test('registration validates input', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
    ])->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('registration rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'password1234',
        'password_confirmation' => 'password1234',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('a user can log in and receives a token', function () {
    User::factory()->create([
        'email' => 'grace@example.com',
        'password' => 'password1234',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'grace@example.com',
        'password' => 'password1234',
    ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'email']]);
});

test('login fails with invalid credentials', function () {
    User::factory()->create([
        'email' => 'grace@example.com',
        'password' => 'password1234',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'grace@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('me returns the authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

test('logout revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

    // The token row is deleted, so it can no longer authenticate any request.
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

test('protected routes reject unauthenticated requests', function () {
    $this->getJson('/api/v1/feeds')->assertUnauthorized();
});
