<?php

use App\Models\Article;
use App\Models\Feed;
use App\Models\User;
use App\Services\FeedService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // No global stub: each test that hits HTTP registers its own fake, so the
    // specific response isn't shadowed. Stray (unfaked) requests fail loudly.
    Http::preventStrayRequests();
    $this->service = app(FeedService::class);
    $this->user = User::factory()->create();
});

test('createFeed assigns the next position for the user', function () {
    Feed::factory()->for($this->user)->create(['position' => 7]);

    $feed = $this->service->createFeed($this->user, [
        'title' => 'New',
        'url' => 'https://example.com/new.xml',
    ]);

    expect($feed->position)->toBe(8)
        ->and($feed->user_id)->toBe($this->user->id);
});

test('createFeed decodes html entities in title and description', function () {
    $feed = $this->service->createFeed($this->user, [
        'title' => 'A &amp; B',
        'url' => 'https://example.com/ent.xml',
        'description' => '&lt;hi&gt;',
    ]);

    expect($feed->title)->toBe('A & B')->and($feed->description)->toBe('<hi>');
});

test('createFeed extracts the title when none is given', function () {
    Http::fake([
        'https://example.com/x.xml' => Http::response(
            '<rss version="2.0"><channel><title>RSS Title</title></channel></rss>', 200,
        ),
    ]);

    $feed = $this->service->createFeed($this->user, ['url' => 'https://example.com/x.xml']);

    expect($feed->title)->toBe('RSS Title');
});

test('updateFeed re-extracts the title when blank', function () {
    Http::fake([
        'https://example.com/u.xml' => Http::response(
            '<feed xmlns="http://www.w3.org/2005/Atom"><title>Atom Title</title></feed>', 200,
        ),
    ]);
    $feed = Feed::factory()->for($this->user)->create();

    $updated = $this->service->updateFeed($feed, ['title' => '', 'url' => 'https://example.com/u.xml']);

    expect($updated->title)->toBe('Atom Title');
});

test('reorder moves the feed and renumbers positions across columns', function () {
    $a = Feed::factory()->for($this->user)->create(['column' => 0, 'position' => 1]);
    $b = Feed::factory()->for($this->user)->create(['column' => 1, 'position' => 2]);
    $c = Feed::factory()->for($this->user)->create(['column' => 1, 'position' => 3]);

    $this->service->reorder($a, 1, 1);

    expect($a->refresh()->column)->toBe(1)
        ->and($b->refresh()->position)->toBe(1)
        ->and($a->position)->toBe(2)
        ->and($c->refresh()->position)->toBe(3);
});

test('markAllAsRead marks unread articles up to the cutoff', function () {
    $feed = Feed::factory()->for($this->user)->create();
    $old = Article::factory()->for($feed)->unread()->create(['published_at' => now()->subDay()]);
    $new = Article::factory()->for($feed)->unread()->create(['published_at' => now()->addDay()]);

    $this->service->markAllAsRead($feed, now()->toDateTimeString());

    expect($old->refresh()->is_read)->toBeTrue()
        ->and($new->refresh()->is_read)->toBeFalse();
});

test('extractTitleFromFeed reads an RSS channel title', function () {
    Http::fake(['https://example.com/rss' => Http::response(
        '<rss version="2.0"><channel><title>RSS Feed Title</title></channel></rss>', 200,
    )]);

    expect($this->service->extractTitleFromFeed('https://example.com/rss'))->toBe('RSS Feed Title');
});

test('extractTitleFromFeed reads an Atom title', function () {
    Http::fake(['https://example.com/atom' => Http::response(
        '<feed xmlns="http://www.w3.org/2005/Atom"><title>Atom Feed Title</title></feed>', 200,
    )]);

    expect($this->service->extractTitleFromFeed('https://example.com/atom'))->toBe('Atom Feed Title');
});

test('extractTitleFromFeed falls back when the request throws', function () {
    Http::fake(['https://example.com/err' => fn () => throw new Exception('boom')]);

    expect($this->service->extractTitleFromFeed('https://example.com/err'))->toBe('Unnamed Feed');
});

test('extractTitleFromFeed falls back on unparseable content', function () {
    Http::fake(['https://example.com/bad' => Http::response('not xml', 200)]);

    expect($this->service->extractTitleFromFeed('https://example.com/bad'))->toBe('Unnamed Feed');
});
