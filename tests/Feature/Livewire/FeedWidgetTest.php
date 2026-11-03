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
        ->assertNotDispatched('article-read')
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
        ->assertNotDispatched('refresh-complete');

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

test('feed widget clears refreshing state on refresh complete', function () {
    // Test the component
    Livewire::test(FeedWidget::class, ['feed' => $this->feed])
        ->set('isRefreshing', true)
        ->call('handleRefreshComplete')
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

test('calls handleArticleRead event handler', function () {
    Livewire::test(FeedWidget::class)
        ->dispatch('article-read', ['foo' => 'bar'])
        ->assertStatus(200);
});
