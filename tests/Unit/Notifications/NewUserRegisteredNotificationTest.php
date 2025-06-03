<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('toArray method returns empty array', function () {
    // Create a mock user
    $user = User::factory()->make([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'created_at' => now(),
    ]);

    // Create the notification
    $notification = new NewUserRegisteredNotification($user);

    // Test the toArray method
    $result = $notification->toArray(null);

    // Assert that the result is an empty array
    expect($result)->toBe([]);
});
