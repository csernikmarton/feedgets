<?php

use App\Models\Article;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    // Feed create/update extracts a title over HTTP and defers a feeds:refresh
    // run; fake all outbound HTTP so tests never touch the network.
    Http::fake(['*' => Http::response('<rss><channel><title>Faked</title></channel></rss>', 200)]);

    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

test('index returns only the authenticated user\'s feeds', function () {
    Feed::factory()->for($this->user)->count(2)->create();
    Feed::factory()->for(User::factory())->create();

    $this->getJson('/api/v1/feeds')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('index includes the unread count for each feed', function () {
    $feed = Feed::factory()->for($this->user)->create();
    Article::factory()->for($feed)->unread()->count(3)->create();
    Article::factory()->for($feed)->read()->count(2)->create();

    $this->getJson('/api/v1/feeds')
        ->assertOk()
        ->assertJsonPath('data.0.unread_count', 3);
});

test('a feed can be created', function () {
    $response = $this->postJson('/api/v1/feeds', [
        'title' => 'My Feed',
        'url' => 'https://example.com/feed.xml',
        'description' => 'Nice feed',
    ]);

    $response->assertCreated()->assertJsonPath('data.title', 'My Feed');

    $this->assertDatabaseHas('feeds', [
        'url' => 'https://example.com/feed.xml',
        'user_id' => $this->user->id,
    ]);
});

test('creating a feed without a title extracts it from the document', function () {
    // The faked feed document (see beforeEach) has the channel title "Faked";
    // an empty title must be populated from it. Exact RSS/Atom parsing is
    // covered in FeedServiceTest.
    $this->postJson('/api/v1/feeds', ['url' => 'https://example.com/notitle.xml'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Faked');
});

test('store validates the url', function () {
    $this->postJson('/api/v1/feeds', ['url' => 'not-a-url'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['url']);
});

test('store rejects a duplicate url for the same user', function () {
    Feed::factory()->for($this->user)->create(['url' => 'https://example.com/dup.xml']);

    $this->postJson('/api/v1/feeds', ['title' => 'Dup', 'url' => 'https://example.com/dup.xml'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['url']);
});

test('the same url may be added by different users', function () {
    Feed::factory()->for(User::factory())->create(['url' => 'https://example.com/shared.xml']);

    $this->postJson('/api/v1/feeds', ['title' => 'Shared', 'url' => 'https://example.com/shared.xml'])
        ->assertCreated();
});

test('a feed can be shown', function () {
    $feed = Feed::factory()->for($this->user)->create();

    $this->getJson("/api/v1/feeds/{$feed->uuid}")
        ->assertOk()
        ->assertJsonPath('data.uuid', (string) $feed->uuid);
});

test('a feed can be updated', function () {
    $feed = Feed::factory()->for($this->user)->create();

    $this->putJson("/api/v1/feeds/{$feed->uuid}", [
        'title' => 'Renamed',
        'url' => $feed->url,
    ])->assertOk()->assertJsonPath('data.title', 'Renamed');

    $this->assertDatabaseHas('feeds', ['uuid' => $feed->uuid, 'title' => 'Renamed']);
});

test('a feed can be deleted', function () {
    $feed = Feed::factory()->for($this->user)->create();

    $this->deleteJson("/api/v1/feeds/{$feed->uuid}")->assertNoContent();

    $this->assertDatabaseMissing('feeds', ['uuid' => $feed->uuid]);
});

test('a user cannot view, update or delete another user\'s feed', function () {
    $feed = Feed::factory()->for(User::factory())->create();

    $this->getJson("/api/v1/feeds/{$feed->uuid}")->assertForbidden();
    $this->putJson("/api/v1/feeds/{$feed->uuid}", ['url' => $feed->url])->assertForbidden();
    $this->deleteJson("/api/v1/feeds/{$feed->uuid}")->assertForbidden();
});

test('a feed can be reordered into another column and position', function () {
    $a = Feed::factory()->for($this->user)->create(['column' => 0, 'position' => 1]);
    $b = Feed::factory()->for($this->user)->create(['column' => 1, 'position' => 2]);

    $this->postJson("/api/v1/feeds/{$a->uuid}/reorder", ['column' => 1, 'index' => 0])
        ->assertOk()
        ->assertJsonPath('data.column', 1);

    expect($a->refresh()->column)->toBe(1);
    expect($a->position)->toBe(1);
    expect($b->refresh()->position)->toBe(2);
});

test('reorder validates the column range', function () {
    $feed = Feed::factory()->for($this->user)->create();

    $this->postJson("/api/v1/feeds/{$feed->uuid}/reorder", ['column' => 5, 'index' => 0])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['column']);
});
