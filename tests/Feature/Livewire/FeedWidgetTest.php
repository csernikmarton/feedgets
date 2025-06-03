<?php

use App\Livewire\FeedWidget;
use App\Models\Article;
use App\Models\Feed;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create a feed for the user
    $this->feed = Feed::factory()->create([
        'user_id' => $this->user->id,
    ]);
});

test('feed widget can be rendered', function () {
    Livewire::test(FeedWidget::class, ['feed' => $this->feed])
        ->assertSuccessful();
});

test('feed widget loads articles on mount', function () {
    // Create some articles for the feed
    $articles = Article::factory()->count(5)->create([
        'feed_uuid' => $this->feed->uuid,
    ]);

    // Test the component
    $component = Livewire::test(FeedWidget::class, ['feed' => $this->feed]);

    // Verify articles are loaded
    expect($component->get('articles'))->toHaveCount(5);
    expect($component->get('totalArticlesLoaded'))->toBe(5);
    expect($component->get('hasMoreArticles'))->toBeFalse();
});

test('feed widget calculates unread count correctly', function () {
    // Create some articles, some read and some unread
    Article::factory()->count(3)->create([
        'feed_uuid' => $this->feed->uuid,
        'is_read' => false, // Unread
    ]);

    Article::factory()->count(2)->create([
        'feed_uuid' => $this->feed->uuid,
        'is_read' => true, // Read
    ]);

    // Test the component
    $component = Livewire::test(FeedWidget::class, ['feed' => $this->feed]);

    // Verify unread count
    expect($component->get('unreadCount'))->toBe(3);
});

test('feed widget can mark an article as read', function () {
    // Create an unread article
    $article = Article::factory()->create([
        'feed_uuid' => $this->feed->uuid,
        'is_read' => false,
    ]);

    // Test the component
    $component = Livewire::test(FeedWidget::class, ['feed' => $this->feed])
        ->assertSet('unreadCount', 1)
        ->call('markArticleAsRead', $article->uuid)
        ->assertSet('unreadCount', 0)
        ->assertDispatched('article-read')
        ->assertDispatched('decrement-total-unread-count');

    // Verify article is marked as read in the database
    $this->assertDatabaseHas('articles', [
        'uuid' => $article->uuid,
        'is_read' => true,
    ]);
});

test('feed widget can mark all articles as read', function () {
    // Create some unread articles with different published dates
    $now = now();

    $article1 = Article::factory()->create([
        'feed_uuid' => $this->feed->uuid,
        'is_read' => false,
        'published_at' => $now->copy()->subDays(2),
    ]);

    $article2 = Article::factory()->create([
        'feed_uuid' => $this->feed->uuid,
        'is_read' => false,
        'published_at' => $now->copy()->subDays(1),
    ]);

    $article3 = Article::factory()->create([
        'feed_uuid' => $this->feed->uuid,
        'is_read' => false,
        'published_at' => $now,
    ]);

    // Test the component
    $component = Livewire::test(FeedWidget::class, ['feed' => $this->feed])
        ->assertSet('unreadCount', 3)
        ->call('markAllAsRead', $now)
        ->assertSet('isRefreshing', true)
        ->assertDispatched('refresh-complete');

    // Verify all articles are marked as read in the database
    $this->assertDatabaseHas('articles', [
        'uuid' => $article1->uuid,
        'is_read' => true,
    ]);

    $this->assertDatabaseHas('articles', [
        'uuid' => $article2->uuid,
        'is_read' => true,
    ]);

    $this->assertDatabaseHas('articles', [
        'uuid' => $article3->uuid,
        'is_read' => true,
    ]);
});

test('feed widget can load more articles', function () {
    // Set a smaller per page value for testing
    $perPage = 5;

    // Create more articles than the per page limit
    $articles = Article::factory()->count($perPage * 2)->create([
        'feed_uuid' => $this->feed->uuid,
    ]);

    // Test the component with the custom perPage value
    $component = Livewire::test(FeedWidget::class, ['feed' => $this->feed])
        ->set('perPage', $perPage);

    // Create a new instance with the same feed to apply the perPage change
    $component = Livewire::test(FeedWidget::class, ['feed' => $this->feed])
        ->set('perPage', $perPage)
        ->assertSet('totalArticlesLoaded', $perPage)
        ->assertSet('hasMoreArticles', true)
        ->call('loadMoreArticles')
        ->assertSet('page', 2)
        ->assertSet('totalArticlesLoaded', $perPage * 2)
        ->assertSet('hasMoreArticles', false);
});

test('feed widget can refresh', function () {
    // Create some articles
    $articles = Article::factory()->count(5)->create([
        'feed_uuid' => $this->feed->uuid,
    ]);

    // Test the component
    $component = Livewire::test(FeedWidget::class, ['feed' => $this->feed])
        ->assertSet('totalArticlesLoaded', 5)
        ->call('refresh')
        ->assertSet('isRefreshing', true)
        ->assertDispatched('calculate-total-unread-count');

    // Create more articles after the initial load
    $newArticles = Article::factory()->count(3)->create([
        'feed_uuid' => $this->feed->uuid,
    ]);

    // Refresh again to see the new articles
    $component->call('refresh')
        ->assertSet('totalArticlesLoaded', 8);
});

test('feed widget handles refresh complete event', function () {
    // Test the component
    Livewire::test(FeedWidget::class, ['feed' => $this->feed])
        ->set('isRefreshing', true)
        ->dispatch('refresh-complete')
        ->assertSet('isRefreshing', false);
});

test('sets hasMoreArticles to true when more articles exist in the database', function () {
    // Make 40 articles in DB
    Article::factory()->count(40)->for($this->feed)->create();

    Livewire::test(FeedWidget::class, ['feed' => $this->feed])
        ->assertSet('hasMoreArticles', true)
        ->assertSet('totalArticlesLoaded', 30);
});

test('does not load more articles if hasMoreArticles is false', function () {
    Livewire::test(FeedWidget::class, ['feed' => $this->feed])
        ->set('hasMoreArticles', false)
        ->call('loadMoreArticles')
        ->assertSet('page', 1); // loadMoreArticles didn't increment the page
});

test('does not load more articles if isLoading is true', function () {
    Livewire::test(FeedWidget::class, ['feed' => $this->feed])
        ->set('isLoading', true)
        ->call('loadMoreArticles')
        ->assertSet('page', 1); // page stays the same
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
    Livewire::test(FeedWidget::class, ['feed' => $feed3])
        ->call('updatePosition', $feed3->uuid, 0, 0, 0);

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
    Livewire::test(FeedWidget::class, ['feed' => $feed1])
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
    $response = Livewire::test(FeedWidget::class, ['feed' => $feed])
        ->call('updatePosition', $feed->uuid, 0, 5, 0);

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
    Livewire::test(FeedWidget::class, ['feed' => $feed])
        ->call('updatePosition', $feed->uuid, 0, 1, 0);

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
    Livewire::test(FeedWidget::class, ['feed' => $feed1])
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
    Livewire::test(FeedWidget::class, ['feed' => Feed::factory()->create([
        'user_id' => $this->user->id,
    ])])
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
    Livewire::test(FeedWidget::class, ['feed' => $feeds->last()])
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

test('calls handleArticleRead event handler', function () {
    Livewire::test(FeedWidget::class)
        ->dispatch('article-read', ['foo' => 'bar'])
        ->assertStatus(200);
});
