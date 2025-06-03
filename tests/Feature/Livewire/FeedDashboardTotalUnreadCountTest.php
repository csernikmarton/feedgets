<?php

use App\Livewire\FeedDashboardTotalUnreadCount;
use App\Models\Article;
use App\Models\Feed;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('component can be rendered', function () {
    Livewire::test(FeedDashboardTotalUnreadCount::class)
        ->assertSuccessful();
});

test('component calculates total unread count correctly', function () {
    // Create a feed for the user
    $feed = Feed::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Create some articles, some read and some unread
    Article::factory()->count(3)->create([
        'feed_uuid' => $feed->uuid,
        'is_read' => false, // Unread
    ]);

    Article::factory()->count(2)->create([
        'feed_uuid' => $feed->uuid,
        'is_read' => true, // Read
    ]);

    // Test the component
    Livewire::test(FeedDashboardTotalUnreadCount::class)
        ->assertSet('totalUnreadCount', 3);
});

test('component only counts unread articles for the authenticated user', function () {
    // Create a feed for the current user
    $userFeed = Feed::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Create unread articles for the current user
    Article::factory()->count(3)->create([
        'feed_uuid' => $userFeed->uuid,
        'is_read' => false,
    ]);

    // Create a feed for another user
    $otherUser = User::factory()->create();
    $otherUserFeed = Feed::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    // Create unread articles for the other user
    Article::factory()->count(5)->create([
        'feed_uuid' => $otherUserFeed->uuid,
        'is_read' => false,
    ]);

    // Test the component
    Livewire::test(FeedDashboardTotalUnreadCount::class)
        ->assertSet('totalUnreadCount', 3); // Only count current user's unread articles
});

test('component can decrement total unread count', function () {
    // Create a feed for the user
    $feed = Feed::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Create some unread articles
    Article::factory()->count(3)->create([
        'feed_uuid' => $feed->uuid,
        'is_read' => false,
    ]);

    // Test the component
    Livewire::test(FeedDashboardTotalUnreadCount::class)
        ->assertSet('totalUnreadCount', 3)
        ->call('decrementTotalUnreadCount')
        ->assertSet('totalUnreadCount', 2);
});

test('component can mark all articles as read', function () {
    // Create a feed for the user
    $feed = Feed::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Create some unread articles
    Article::factory()->count(5)->create([
        'feed_uuid' => $feed->uuid,
        'is_read' => false,
    ]);

    // Test the component
    Livewire::test(FeedDashboardTotalUnreadCount::class)
        ->assertSet('totalUnreadCount', 5)
        ->call('markAllArticlesAsRead')
        ->assertDispatched('article-read')
        ->assertDispatched('calculate-total-unread-count')
        ->assertSet('totalUnreadCount', 0);

    // Verify all articles are marked as read in the database
    $this->assertDatabaseCount('articles', 5);
    $this->assertEquals(5, \Illuminate\Support\Facades\DB::table('articles')->where('is_read', true)->count());
    $this->assertEquals(0, \Illuminate\Support\Facades\DB::table('articles')->where('is_read', false)->count());
});

test('component recalculates total unread count when event is dispatched', function () {
    // Create a feed for the user
    $feed = Feed::factory()->create([
        'user_id' => $this->user->id,
    ]);

    // Create some unread articles
    Article::factory()->count(3)->create([
        'feed_uuid' => $feed->uuid,
        'is_read' => false,
    ]);

    // Test the component
    $component = Livewire::test(FeedDashboardTotalUnreadCount::class)
        ->assertSet('totalUnreadCount', 3);

    // Create more unread articles
    Article::factory()->count(2)->create([
        'feed_uuid' => $feed->uuid,
        'is_read' => false,
    ]);

    // Dispatch the event to recalculate
    $component->dispatch('calculate-total-unread-count')
        ->assertSet('totalUnreadCount', 5);
});
