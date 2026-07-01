<?php

use App\Models\Article;
use App\Models\Feed;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    $this->feed = Feed::factory()->for($this->user)->create();
});

test('articles for a feed are paginated', function () {
    Article::factory()->for($this->feed)->count(40)->create();

    $this->getJson("/api/v1/feeds/{$this->feed->uuid}/articles")
        ->assertOk()
        ->assertJsonCount(30, 'data')
        ->assertJsonPath('meta.total', 40)
        ->assertJsonPath('meta.per_page', 30);
});

test('per_page is honoured and capped', function () {
    Article::factory()->for($this->feed)->count(10)->create();

    $this->getJson("/api/v1/feeds/{$this->feed->uuid}/articles?per_page=5")
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

test('the unread filter returns only unread articles', function () {
    Article::factory()->for($this->feed)->unread()->count(3)->create();
    Article::factory()->for($this->feed)->read()->count(4)->create();

    $this->getJson("/api/v1/feeds/{$this->feed->uuid}/articles?unread=1")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('the global articles endpoint spans the user\'s feeds only', function () {
    Article::factory()->for($this->feed)->count(2)->create();
    $otherFeed = Feed::factory()->for(User::factory())->create();
    Article::factory()->for($otherFeed)->count(5)->create();

    $this->getJson('/api/v1/articles')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});

test('an article can be shown', function () {
    $article = Article::factory()->for($this->feed)->create();

    $this->getJson("/api/v1/articles/{$article->uuid}")
        ->assertOk()
        ->assertJsonPath('data.uuid', (string) $article->uuid);
});

test('an article can be marked read', function () {
    $article = Article::factory()->for($this->feed)->unread()->create();

    $this->postJson("/api/v1/articles/{$article->uuid}/mark-read")
        ->assertOk()
        ->assertJsonPath('data.is_read', true);

    expect($article->refresh()->is_read)->toBeTrue();
});

test('mark all read respects the published_at cutoff', function () {
    $old = Article::factory()->for($this->feed)->unread()->create(['published_at' => now()->subDays(5)]);
    $new = Article::factory()->for($this->feed)->unread()->create(['published_at' => now()->addDay()]);

    $this->postJson("/api/v1/feeds/{$this->feed->uuid}/mark-all-read", [
        'published_at' => now()->toDateTimeString(),
    ])->assertOk()->assertJsonPath('unread_count', 1);

    expect($old->refresh()->is_read)->toBeTrue();
    expect($new->refresh()->is_read)->toBeFalse();
});

test('mark all read without a cutoff marks everything read', function () {
    Article::factory()->for($this->feed)->unread()->count(4)->create(['published_at' => now()->subHour()]);

    $this->postJson("/api/v1/feeds/{$this->feed->uuid}/mark-all-read")
        ->assertOk()
        ->assertJsonPath('unread_count', 0);
});

test('a user cannot touch articles belonging to another user', function () {
    $otherFeed = Feed::factory()->for(User::factory())->create();
    $article = Article::factory()->for($otherFeed)->unread()->create();

    $this->getJson("/api/v1/articles/{$article->uuid}")->assertForbidden();
    $this->postJson("/api/v1/articles/{$article->uuid}/mark-read")->assertForbidden();
    $this->getJson("/api/v1/feeds/{$otherFeed->uuid}/articles")->assertForbidden();
    $this->postJson("/api/v1/feeds/{$otherFeed->uuid}/mark-all-read")->assertForbidden();
});
