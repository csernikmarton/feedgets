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
    $response = $this->get(route('dashboard'));
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

test('can update feed position within the same column', function () {
    $feed1 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'column' => 0,
        'position' => 1,
    ]);

    $feed2 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'column' => 0,
        'position' => 2,
    ]);

    $feed3 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'column' => 0,
        'position' => 3,
    ]);

    // Test moving feed3 to position 1 in the same column
    Livewire::test(FeedDashboard::class)
        ->dispatch('update-position', feedId: $feed3->uuid, fromColumn: 0, toColumn: 0, newIndex: 0)
        ->assertDispatched('position-updated');

    // Refresh models from database
    $feed1->refresh();
    $feed2->refresh();
    $feed3->refresh();

    // Check that positions were updated correctly
    expect($feed3->position)->toBe(1);
    expect($feed1->position)->toBe(2);
    expect($feed2->position)->toBe(3);
    expect($feed3->column)->toBe(0);
});

test('can move feed to a different column', function () {
    $feed1 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'column' => 0,
        'position' => 1,
    ]);

    $feed2 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'column' => 1,
        'position' => 2,
    ]);

    // Test moving feed1 to column 1 at position 0
    Livewire::test(FeedDashboard::class)
        ->call('updatePosition', $feed1->uuid, 0, 1, 0);

    // Refresh models from database
    $feed1->refresh();
    $feed2->refresh();

    // Check that positions and columns were updated correctly
    expect($feed1->column)->toBe(1);
    expect($feed1->position)->toBe(1);
    expect($feed2->position)->toBe(2);
});

test('validates input data for updatePosition', function () {
    $feed = Feed::factory()->create([
        'user_id' => $this->user->id,
        'column' => 0,
        'position' => 1,
    ]);

    // Test with invalid column value
    $response = Livewire::test(FeedDashboard::class)
        ->call('updatePosition', $feed->uuid, 0, 5, 0)
        ->assertDispatched('position-update-failed');

    // Feed should remain in the original position
    $feed->refresh();
    expect($feed->column)->toBe(0);
    expect($feed->position)->toBe(1);
});

test('prevents unauthorized feed position updates', function () {
    // Create a feed owned by one user
    $user1 = User::factory()->create();
    $feed = Feed::factory()->create([
        'user_id' => $user1->id,
        'column' => 0,
        'position' => 1,
    ]);

    // Login as a different user
    $user2 = User::factory()->create();
    $this->actingAs($user2);

    // Attempt to update the feed position
    Livewire::test(FeedDashboard::class)
        ->call('updatePosition', $feed->uuid, 0, 1, 0)
        ->assertRedirect(route('dashboard'));

    // Feed should remain in the original position
    $feed->refresh();
    expect($feed->column)->toBe(0);
    expect($feed->position)->toBe(1);
});

test('handles multiple feeds in target column correctly', function () {
    // Create feeds in different columns
    $feed1 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'column' => 0,
        'position' => 1,
    ]);

    $feed2 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'column' => 1,
        'position' => 1,
    ]);

    $feed3 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'column' => 1,
        'position' => 2,
    ]);

    $feed4 = Feed::factory()->create([
        'user_id' => $this->user->id,
        'column' => 1,
        'position' => 3,
    ]);

    // Move feed1 to column 1 at position 1 (between feed2 and feed3)
    Livewire::test(FeedDashboard::class)
        ->call('updatePosition', $feed1->uuid, 0, 1, 1);

    // Refresh models from database
    $feed1->refresh();
    $feed2->refresh();
    $feed3->refresh();
    $feed4->refresh();

    // Check that positions were updated correctly
    expect($feed1->column)->toBe(1);
    expect($feed2->position)->toBe(1);
    expect($feed1->position)->toBe(2);
    expect($feed3->position)->toBe(3);
    expect($feed4->position)->toBe(4);
});

test('handles non-existent feed gracefully', function () {
    $nonExistentUuid = '00000000-0000-0000-0000-000000000000';

    // Test with non-existent feed UUID
    Livewire::test(FeedDashboard::class)
        ->call('updatePosition', $nonExistentUuid, 0, 1, 0);

    // No exception should be thrown
    expect(true)->toBeTrue();
});

test('handles database transaction correctly', function () {
    // Create multiple feeds
    $feeds = Feed::factory()->count(5)->create([
        'user_id' => $this->user->id,
        'column' => 0,
    ]);

    // Set sequential positions
    $position = 1;
    foreach ($feeds as $feed) {
        $feed->update(['position' => $position++]);
    }

    // Move the last feed to the first position
    Livewire::test(FeedDashboard::class)
        ->call('updatePosition', $feeds->last()->uuid, 0, 0, 0);

    // Verify all feeds have unique, sequential positions
    $updatedFeeds = Feed::where('user_id', $this->user->id)
        ->where('column', 0)
        ->orderBy('position')
        ->get();

    $positions = $updatedFeeds->pluck('position')->toArray();
    $uniquePositions = array_unique($positions);

    // Check that we have the same number of unique positions as feeds
    expect(count($uniquePositions))->toBe(count($feeds));

    // Check that positions are sequential
    $expectedPositions = range(1, count($feeds));
    expect($positions)->toEqual($expectedPositions);
});
