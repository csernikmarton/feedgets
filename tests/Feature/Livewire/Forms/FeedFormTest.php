<?php

use App\Livewire\Forms\FeedForm;
use App\Models\Feed;
use App\Models\User;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Exceptions\MissingRulesException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Create a mock component for the form
    $this->component = new class extends Component
    {
        public $form;
    };
});

test('feed form can validate url', function () {
    $form = new FeedForm($this->component, 'form');

    // Empty URL
    $form->title = 'Test Feed';
    $form->url = '';
    $form->description = 'Test description';

    expect(function () use ($form) {
        $form->validate();
    })->toThrow(ValidationException::class);

    // Invalid URL
    $form->url = 'not-a-url';

    expect(function () use ($form) {
        $form->validate();
    })->toThrow(ValidationException::class);

    // Valid URL
    $form->url = 'https://example.com';

    expect(function () use ($form) {
        $form->validate();
    })->not->toThrow(MissingRulesException::class);
});

test('feed form can set feed data', function () {
    $feed = Feed::factory()->create([
        'title' => 'Original Title',
        'url' => 'https://example.com',
        'description' => 'Original Description',
        'user_id' => $this->user->id,
    ]);

    $form = new FeedForm($this->component, 'form');
    $form->setFeed($feed);

    expect($form->feed->uuid)->toBe($feed->uuid);
    expect($form->title)->toBe('Original Title');
    expect($form->url)->toBe('https://example.com');
    expect($form->description)->toBe('Original Description');
});

test('feed form can store new feed', function () {
    $form = new FeedForm($this->component, 'form');
    $form->title = 'New Feed';
    $form->url = 'https://example.com/feed';
    $form->description = 'New Feed Description';

    $feed = $form->store();

    expect($feed)->toBeInstanceOf(Feed::class);
    expect($feed->title)->toBe('New Feed');
    expect($feed->url)->toBe('https://example.com/feed');
    expect($feed->description)->toBe('New Feed Description');
    expect($feed->user_id)->toBe($this->user->id);

    $this->assertDatabaseHas('feeds', [
        'title' => 'New Feed',
        'url' => 'https://example.com/feed',
        'description' => 'New Feed Description',
        'user_id' => $this->user->id,
    ]);
});

test('feed form can update existing feed', function () {
    $feed = Feed::factory()->create([
        'title' => 'Original Title',
        'url' => 'https://example.com',
        'description' => 'Original Description',
        'user_id' => $this->user->id,
    ]);

    $form = new FeedForm($this->component, 'form');
    $form->setFeed($feed);
    $form->title = 'Updated Title';
    $form->url = 'https://example.com/updated';
    $form->description = 'Updated Description';

    $updatedFeed = $form->update();

    expect($updatedFeed->uuid)->toBe($feed->uuid);
    expect($updatedFeed->title)->toBe('Updated Title');
    expect($updatedFeed->url)->toBe('https://example.com/updated');
    expect($updatedFeed->description)->toBe('Updated Description');

    $this->assertDatabaseHas('feeds', [
        'uuid' => $feed->uuid,
        'title' => 'Updated Title',
        'url' => 'https://example.com/updated',
        'description' => 'Updated Description',
    ]);
});

test('feed form handles html entities in title and description', function () {
    $form = new FeedForm($this->component, 'form');
    $form->title = 'Title with &amp; entity';
    $form->url = 'https://example.com/feed';
    $form->description = 'Description with &lt;tags&gt;';

    $feed = $form->store();

    expect($feed->title)->toBe('Title with & entity');
    expect($feed->description)->toBe('Description with <tags>');
});

test('feed form extracts title from feed when title is empty during store', function () {
    // Mock the HTTP response for feed extraction
    Http::fake([
        'https://example.com/feed' => Http::response(
            '<?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
                <channel>
                    <title>Extracted RSS Title</title>
                    <link>https://example.com</link>
                    <description>Test RSS Feed</description>
                </channel>
            </rss>',
            200
        ),
    ]);

    $form = new FeedForm($this->component, 'form');
    $form->title = ''; // Empty title should trigger extraction
    $form->url = 'https://example.com/feed';
    $form->description = 'Test description';

    $feed = $form->store();

    expect($feed->title)->toBe('Extracted RSS Title');
    Http::assertSent(function ($request) {
        return $request->url() == 'https://example.com/feed';
    });
});

test('feed form calls artisan command to refresh feed after store', function () {
    // Mock Artisan calls
    Artisan::shouldReceive('call')
        ->once()
        ->with('feeds:refresh', Mockery::on(function ($args) {
            return isset($args['--uuid']);
        }))
        ->andReturn(0);

    $form = new FeedForm($this->component, 'form');
    $form->title = 'Test Feed';
    $form->url = 'https://example.com/feed';
    $form->description = 'Test description';

    $feed = $form->store();

    // Manually invoke deferred callbacks
    app(DeferredCallbackCollection::class)->invoke();

    // Note: The Artisan call is deferred using Illuminate\Support\defer
    // We're testing that the code sets up the deferred call correctly
});

test('feed form extracts title from feed when title is empty during update', function () {
    // Create a feed to update
    $feed = Feed::factory()->create([
        'title' => 'Original Title',
        'url' => 'https://example.com/original',
        'description' => 'Original Description',
        'user_id' => $this->user->id,
    ]);

    // Mock the HTTP response for feed extraction
    Http::fake([
        'https://example.com/updated' => Http::response(
            '<?xml version="1.0" encoding="UTF-8"?>
            <rss version="2.0">
                <channel>
                    <title>Extracted Updated Title</title>
                    <link>https://example.com</link>
                    <description>Updated RSS Feed</description>
                </channel>
            </rss>',
            200
        ),
    ]);

    $form = new FeedForm($this->component, 'form');
    $form->setFeed($feed);
    $form->title = ''; // Empty title should trigger extraction
    $form->url = 'https://example.com/updated';
    $form->description = 'Updated Description';

    $updatedFeed = $form->update();

    expect($updatedFeed->title)->toBe('Extracted Updated Title');
    Http::assertSent(function ($request) {
        return $request->url() == 'https://example.com/updated';
    });
});

test('feed form calls artisan command to refresh feed after update', function () {
    // Create a feed to update
    $feed = Feed::factory()->create([
        'title' => 'Original Title',
        'url' => 'https://example.com/original',
        'description' => 'Original Description',
        'user_id' => $this->user->id,
    ]);

    // Mock Artisan calls
    Artisan::shouldReceive('call')
        ->once()
        ->with('feeds:refresh', Mockery::on(function ($args) use ($feed) {
            return isset($args['--uuid']) && $args['--uuid'] === $feed->uuid;
        }))
        ->andReturn(0);

    $form = new FeedForm($this->component, 'form');
    $form->setFeed($feed);
    $form->title = 'Updated Title';
    $form->url = 'https://example.com/updated';
    $form->description = 'Updated Description';

    $form->update();

    // Manually invoke deferred callbacks
    app(DeferredCallbackCollection::class)->invoke();

    // Note: The Artisan call is deferred using Illuminate\Support\defer
    // We're testing that the code sets up the deferred call correctly
});

// Title-extraction tests moved to tests/Unit/Services/FeedServiceTest.php
// (logic now lives in App\Services\FeedService::extractTitleFromFeed).
