<?php

use App\Models\User;

test('guests see the landing page at the root url', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('All your feeds on one page.')
        ->assertSee(route('register'));
});

test('authenticated users are redirected from the root url to the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('dashboard'));
});

test('authenticated users can reach the dashboard at its own url', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk();
});

test('guests are redirected from the dashboard to the login page', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('unverified users are redirected from the dashboard to the verification notice', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});
