<?php

use App\Livewire\FeedDashboard;
use App\Models\Feed;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('feed dashboard can be rendered', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
});

test('feed dashboard displays user feeds in columns', function () {
    // Create feeds for the user with different positions
    $feed1 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'position' => 1,
        'column' => 1,
    ]);

    $feed2 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'position' => 2,
        'column' => 2,
    ]);

    $feed3 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'position' => 3,
        'column' => 0,
    ]);

    // Test the component
    $component = Livewire::test(FeedDashboard::class);

    // Get the columns from the component
    $columns = $component->viewData('columns');

    // Verify feeds are distributed correctly based on position % 3
    expect($columns[1]->pluck('uuid')->contains($feed1->uuid))->toBeTrue(); // position 1 % 3 = 1
    expect($columns[2]->pluck('uuid')->contains($feed2->uuid))->toBeTrue(); // position 2 % 3 = 2
    expect($columns[0]->pluck('uuid')->contains($feed3->uuid))->toBeTrue(); // position 3 % 3 = 0
});

test('feed dashboard respects column value when available', function () {
    // Create feeds for the user with explicit column values
    $feed1 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'position' => 1,
        'column' => 0, // Explicitly set to column 0
    ]);

    $feed2 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'position' => 2,
        'column' => 1, // Explicitly set to column 1
    ]);

    $feed3 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'position' => 3,
        'column' => 2, // Explicitly set to column 2
    ]);

    // Test the component
    $component = Livewire::test(FeedDashboard::class);

    // Get the columns from the component
    $columns = $component->viewData('columns');

    // Verify feeds are distributed according to their column value
    expect($columns[0]->pluck('uuid')->contains($feed1->uuid))->toBeTrue();
    expect($columns[1]->pluck('uuid')->contains($feed2->uuid))->toBeTrue();
    expect($columns[2]->pluck('uuid')->contains($feed3->uuid))->toBeTrue();
});

test('feed dashboard handles out of bounds column values', function () {
    // Create a feed with an out of bounds column value
    $feed = Feed::factory()->create([
        'user_id' => $this->user->id,
        'position' => 1,
        'column' => 5, // Out of bounds (should be clamped to 2)
    ]);

    // Test the component
    $component = Livewire::test(FeedDashboard::class);

    // Get the columns from the component
    $columns = $component->viewData('columns');

    // Verify feed is placed in column 2 (the maximum valid column)
    expect($columns[2]->pluck('uuid')->contains($feed->uuid))->toBeTrue();
});

test('feed dashboard only shows feeds for authenticated user', function () {
    // Create a feed for the current user
    $userFeed = Feed::factory()->create([
        'user_id' => $this->user->id,
        'position' => 1,
    ]);

    // Create a feed for another user
    $otherUser = User::factory()->create();
    $otherUserFeed = Feed::factory()->create([
        'user_id' => $otherUser->id,
        'position' => 1,
    ]);

    // Test the component
    $component = Livewire::test(FeedDashboard::class);

    // Get all feeds from all columns
    $columns = $component->viewData('columns');
    $allFeeds = $columns[0]->concat($columns[1])->concat($columns[2]);

    // Verify only the current user's feed is included
    expect($allFeeds->pluck('uuid')->contains($userFeed->uuid))->toBeTrue();
    expect($allFeeds->pluck('uuid')->contains($otherUserFeed->uuid))->toBeFalse();
});

test('refreshFeedDisplay dispatches expected events', function () {
    Livewire::test(FeedDashboard::class)
        ->call('refreshFeedDisplay')
        ->assertDispatched('calculate-total-unread-count')
        ->assertDispatched('refresh');
});
